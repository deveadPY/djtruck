<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Application\Sales\DTOs\PayInstallmentData;
use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Events\Listeners\SendCuotaPagadaEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayInstallmentUseCase — registra el cobro de una cuota.
 *
 * Unifica la lógica que antes estaba duplicada en:
 *  - PlanCuotasWebController::pagarCuota()
 *  - InstallmentController::pay()
 *
 * Responsabilidades:
 *  1. Guard: cuota ya pagada → lanza DomainException
 *  2. Calcula interés de mora si corresponde
 *  3. Actualiza estado de la cuota en BD
 *  4. Registra movimiento de ingreso en Caja
 *  5. Envía recibo por email (falla silenciosamente)
 */
final class PayInstallmentUseCase
{
    public function __construct(
        private readonly CajaService          $cajaService,
        private readonly SendCuotaPagadaEmail $emailRecibo,
    ) {}

    /** @throws \DomainException si la cuota ya estaba pagada */
    public function execute(PayInstallmentData $data): object
    {
        $cuota = DB::table('cuotas')
            ->where('id', $data->cuotaId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if ($cuota->estado === 'PAGADA') {
            throw new \DomainException('Esta cuota ya fue registrada como pagada.');
        }

        $interesExtra = $this->calcularInteresExtra($cuota);
        $cajaId       = $data->cajaId ?? $this->cajaService->cajaCapitalId();

        DB::table('cuotas')->where('id', $data->cuotaId)->update([
            'estado'              => 'PAGADA',
            'fecha_pago_efectivo' => $data->fechaPago,
            'monto_pagado'        => $data->montoPagado,
            'interes_mora'        => $interesExtra,
            'caja_cobro_id'       => $cajaId,
            'updated_by'          => $data->userId,
            'updated_at'          => now(),
        ]);

        $this->registrarEnCaja($cuota, $data, $cajaId);
        $this->enviarRecibo($data);

        return DB::table('cuotas')->where('id', $data->cuotaId)->first();
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    private function calcularInteresExtra(object $cuota): float
    {
        if (!in_array($cuota->estado, ['PENDIENTE', 'VENCIDA', 'EN_MORA'], true)) {
            return 0.0;
        }

        $vencimiento = Carbon::parse($cuota->fecha_vencimiento);
        $diasMora    = max(0, (int) $vencimiento->diffInDays(now(), absolute: false) * -1);
        // diffInDays negativo significa que ya pasó la fecha
        $diasMora    = $vencimiento->isPast() ? (int) $vencimiento->diffInDays(now()) : 0;

        if ($diasMora <= 0) {
            return 0.0;
        }

        $tasaMoraDiaria = (float) config('erp.installments.tasa_mora_diaria_pct', 0.1);
        $montoTotal     = (float) $cuota->capital + (float) $cuota->interes;

        return round($montoTotal * ($tasaMoraDiaria / 100) * $diasMora, 2);
    }

    private function registrarEnCaja(object $cuota, PayInstallmentData $data, int $cajaId): void
    {
        $venta = DB::table('ventas')->where('id', $cuota->venta_id)->first();
        $ref   = $venta ? "Venta {$venta->numero_venta}" : "Venta #{$cuota->venta_id}";

        try {
            $this->cajaService->registrar(
                cajaId:       $cajaId,
                tipo:         'INGRESO',
                concepto:     "Cobro cuota #{$cuota->numero_cuota}/{$cuota->total_cuotas} — {$ref}",
                moneda:       'USD',
                monto:        $data->montoPagado,
                montoUsd:     $data->montoPagado,
                referenciaId: $data->cuotaId,
                refType:      'cuota',
            );
        } catch (\RuntimeException $e) {
            Log::warning('PayInstallmentUseCase: no se pudo registrar en caja: ' . $e->getMessage());
        }
    }

    private function enviarRecibo(PayInstallmentData $data): void
    {
        try {
            $this->emailRecibo->sendRecibo($data->cuotaId, $data->userId);
        } catch (\Throwable) {
            // El email nunca debe interrumpir el cobro
        }
    }
}
