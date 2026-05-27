<?php

declare(strict_types=1);

namespace App\Application\Commissions;

use App\Domain\Commissions\ValueObjects\CommissionStatus;
use App\Domain\Finance\Services\CajaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Marca una comisión como pagada y genera EGRESO en Caja Capital.
 */
final class PayCommissionUseCase
{
    public function __construct(
        private readonly CajaService $cajaService,
    ) {}

    public function execute(int $comisionId): void
    {
        $row = DB::table('comisiones_calculadas')->where('id', $comisionId)->first();
        if (!$row) {
            throw new RuntimeException("Comisión {$comisionId} no encontrada.");
        }

        $estadoActual = CommissionStatus::from($row->estado);
        if (!$estadoActual->puedeTransicionarA(CommissionStatus::PAGADA)) {
            throw new RuntimeException("La comisión está en {$row->estado} y no puede pagarse.");
        }

        $vendedor = DB::table('users')->where('id', $row->vendedor_id)->first();
        $vendedorNombre = $vendedor->name ?? "Vendedor #{$row->vendedor_id}";

        DB::transaction(function () use ($row, $vendedorNombre, $comisionId) {
            $movimientoId = $this->cajaService->registrar(
                cajaId:       $this->cajaService->cajaCapitalId(),
                tipo:         'EGRESO',
                concepto:     "Pago comisión venta #{$row->venta_id} — {$vendedorNombre}",
                moneda:       'USD',
                monto:        (float) $row->monto_comision_usd,
                montoUsd:     (float) $row->monto_comision_usd,
                referenciaId: $comisionId,
                refType:      'comision',
            );

            DB::table('comisiones_calculadas')->where('id', $comisionId)->update([
                'estado'              => 'PAGADA',
                'fecha_pago'          => now()->toDateString(),
                'movimiento_caja_id'  => $movimientoId,
                'updated_at'          => now(),
            ]);
        });
    }
}
