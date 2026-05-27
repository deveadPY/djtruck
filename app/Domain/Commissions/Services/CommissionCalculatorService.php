<?php

declare(strict_types=1);

namespace App\Domain\Commissions\Services;

use Illuminate\Support\Facades\DB;

/**
 * Calcula la comisión de una venta según el esquema vigente del vendedor.
 *
 * Resolución de esquema:
 *  1. Esquema específico del vendedor activo y vigente.
 *  2. Si no hay, esquema GLOBAL (vendedor_id = null) activo y vigente.
 *  3. Si no hay, devuelve null (no aplica comisión).
 */
final class CommissionCalculatorService
{
    /**
     * @return array{esquema_id: int, base_usd: float, porcentaje: ?float, comision_usd: float} | null
     */
    public function calcular(int $vendedorId, int $ventaId): ?array
    {
        $venta = DB::table('ventas')
            ->where('id', $ventaId)
            ->whereNull('deleted_at')
            ->first();
        if (!$venta) {
            return null;
        }

        $esquema = $this->buscarEsquemaVigente($vendedorId, $venta->fecha_venta);
        if (!$esquema) {
            return null;
        }

        $baseUsd = match ($esquema->tipo_calculo) {
            'PCT_VENTA'      => (float) $venta->precio_venta_usd - (float) ($venta->descuento_usd ?? 0),
            'PCT_MARGEN'     => (float) ($venta->margen_bruto_usd ?? 0),
            'FIJO_POR_VENTA' => 0,
            'ESCALONADO'     => (float) ($venta->margen_bruto_usd ?? 0),
            default          => 0,
        };

        $comision = match ($esquema->tipo_calculo) {
            'PCT_VENTA',
            'PCT_MARGEN'     => round($baseUsd * ((float) $esquema->porcentaje / 100), 4),
            'FIJO_POR_VENTA' => (float) $esquema->monto_fijo_usd,
            'ESCALONADO'     => $this->calcularEscalonado($baseUsd, $esquema->escala),
            default          => 0,
        };

        return [
            'esquema_id'   => (int) $esquema->id,
            'base_usd'     => $baseUsd,
            'porcentaje'   => in_array($esquema->tipo_calculo, ['PCT_VENTA', 'PCT_MARGEN'], true)
                ? (float) $esquema->porcentaje
                : null,
            'comision_usd' => max(0, $comision),
        ];
    }

    private function buscarEsquemaVigente(int $vendedorId, string $fechaVenta): ?object
    {
        // Específico al vendedor
        $esquema = DB::table('esquemas_comision')
            ->where('vendedor_id', $vendedorId)
            ->where('activo', true)
            ->where('vigencia_desde', '<=', $fechaVenta)
            ->where(function ($q) use ($fechaVenta) {
                $q->whereNull('vigencia_hasta')->orWhere('vigencia_hasta', '>=', $fechaVenta);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('vigencia_desde')
            ->first();

        if ($esquema) {
            return $esquema;
        }

        // Global
        return DB::table('esquemas_comision')
            ->whereNull('vendedor_id')
            ->where('activo', true)
            ->where('vigencia_desde', '<=', $fechaVenta)
            ->where(function ($q) use ($fechaVenta) {
                $q->whereNull('vigencia_hasta')->orWhere('vigencia_hasta', '>=', $fechaVenta);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('vigencia_desde')
            ->first();
    }

    private function calcularEscalonado(float $baseUsd, ?string $escalaJson): float
    {
        $escala = json_decode($escalaJson ?? '[]', true);
        if (!is_array($escala) || empty($escala)) {
            return 0;
        }
        foreach ($escala as $rango) {
            $desde = (float) ($rango['desde'] ?? 0);
            $hasta = isset($rango['hasta']) ? (float) $rango['hasta'] : PHP_FLOAT_MAX;
            $pct   = (float) ($rango['pct'] ?? 0);
            if ($baseUsd >= $desde && $baseUsd <= $hasta) {
                return round($baseUsd * $pct / 100, 4);
            }
        }
        return 0;
    }
}
