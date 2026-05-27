<?php

declare(strict_types=1);

namespace App\Application\Commissions;

use App\Domain\Commissions\Services\CommissionCalculatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Calcula y persiste la comisión de una venta. Idempotente:
 * si ya existe comisión para (venta_id, vendedor_id), retorna esa.
 */
final class CalculateCommissionUseCase
{
    public function __construct(
        private readonly CommissionCalculatorService $calculator,
    ) {}

    public function execute(int $ventaId): ?int
    {
        $venta = DB::table('ventas')
            ->where('id', $ventaId)
            ->whereNull('deleted_at')
            ->first();
        if (!$venta) {
            throw new RuntimeException("Venta {$ventaId} no encontrada.");
        }
        if (!$venta->vendedor_id) {
            return null; // venta sin vendedor — sin comisión
        }
        if (!in_array($venta->estado, ['COMPLETADO', 'COMPLETADA'], true)) {
            return null; // solo ventas completadas generan comisión
        }

        // Idempotencia
        $existing = DB::table('comisiones_calculadas')
            ->where('venta_id', $ventaId)
            ->where('vendedor_id', $venta->vendedor_id)
            ->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $calc = $this->calculator->calcular((int) $venta->vendedor_id, $ventaId);
        if (!$calc) {
            Log::info('commission.no_scheme', ['venta_id' => $ventaId, 'vendedor_id' => $venta->vendedor_id]);
            return null;
        }

        return DB::transaction(function () use ($venta, $calc) {
            return DB::table('comisiones_calculadas')->insertGetId([
                'venta_id'            => $venta->id,
                'vendedor_id'         => $venta->vendedor_id,
                'esquema_id'          => $calc['esquema_id'],
                'fecha_venta'         => $venta->fecha_venta,
                'base_calculo_usd'    => $calc['base_usd'],
                'porcentaje_aplicado' => $calc['porcentaje'],
                'monto_comision_usd'  => $calc['comision_usd'],
                'estado'              => 'CALCULADA',
                'created_by'          => Auth::id(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        });
    }
}
