<?php

declare(strict_types=1);

namespace App\Domain\Sales\Processors;

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentProcessor
{
    private const CASH_TYPES = ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'TARJETA'];

    public function __construct(
        private readonly CajaService $cajaService,
        private readonly SaleRepositoryInterface $saleRepository
    ) {}

    public function process(SaleModel $venta, array $pagos, string $modalidad): float
    {
        $totalUsd = 0.0;

        foreach ($pagos as $pago) {
            $montoUsd = (float) ($pago['monto_usd'] ?? 0);
            if ($montoUsd <= 0) {
                continue;
            }

            $totalUsd += $montoUsd;

            $this->insertPaymentDetail($venta, $pago, $montoUsd, $modalidad);
            $this->processTradeInVehicle($pago);
            $this->registerInCashRegister($venta, $pago, $modalidad, $montoUsd);
        }

        return $totalUsd;
    }

    private function insertPaymentDetail(SaleModel $venta, array $pago, float $montoUsd, string $modalidad): void
    {
        $this->saleRepository->addPayment($venta->id, [
            'tipo_pago' => $pago['tipo'] ?? 'EFECTIVO',
            'moneda' => 'USD',
            'monto_moneda' => $montoUsd,
            'monto_usd' => $montoUsd,
            'tasa_cambio' => 1,
            'vehiculo_canje_id' => $this->resolveTradeInVehicleId($pago),
            'referencia_bancaria' => $pago['referencia'] ?? null,
            'fecha_pago' => $venta->fecha_venta,
            'observaciones' => $modalidad === 'CUOTAS' ? 'Entrega inicial' : null,
            'created_by' => Auth::id(),
        ]);
    }

    private function processTradeInVehicle(array $pago): void
    {
        if ($this->isTradeIn($pago)) {
            DB::table('vehiculos')
                ->where('id', $pago['vehiculo_canje_id'])
                ->update(['estado' => 'TOMA', 'updated_at' => now()]);
        }
    }

    private function registerInCashRegister(SaleModel $venta, array $pago, string $modalidad, float $montoUsd): void
    {
        $tipoPago = $pago['tipo'] ?? 'EFECTIVO';

        if (!in_array($tipoPago, self::CASH_TYPES, true)) {
            return;
        }

        $tipoLabel = $this->getPaymentLabel($tipoPago);
        $modalidadLabel = $modalidad === 'CUOTAS' ? 'entrega inicial' : 'contado';

        try {
            $this->cajaService->ingresoCapital(
                "Venta {$venta->numero_venta} – {$tipoLabel} ({$modalidadLabel})",
                'USD',
                $montoUsd,
                $montoUsd,
                $venta->id,
                'venta'
            );
        } catch (RuntimeException $e) {
            Log::warning('PaymentProcessor: no se pudo registrar movimiento en caja', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function revert(int $ventaId): void
    {
        $payments = $this->saleRepository->getPayments($ventaId);

        foreach ($payments as $payment) {
            if ($payment->vehiculo_canje_id) {
                DB::table('vehiculos')
                    ->where('id', $payment->vehiculo_canje_id)
                    ->update(['estado' => 'DISPONIBLE', 'updated_at' => now()]);
            }
        }

        $this->saleRepository->removePayments($ventaId);
    }

    private function isTradeIn(array $pago): bool
    {
        return ($pago['tipo'] ?? null) === 'VEHICULO_CANJE'
            && !empty($pago['vehiculo_canje_id']);
    }

    private function resolveTradeInVehicleId(array $pago): ?int
    {
        return $this->isTradeIn($pago) ? (int) $pago['vehiculo_canje_id'] : null;
    }

    private function getPaymentLabel(string $tipo): string
    {
        return match ($tipo) {
            'EFECTIVO'      => 'Efectivo',
            'TRANSFERENCIA' => 'Transferencia',
            'CHEQUE'        => 'Cheque',
            'TARJETA'       => 'Tarjeta',
            default         => $tipo,
        };
    }
}
