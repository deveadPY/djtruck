<?php

declare(strict_types=1);

namespace App\Domain\Suppliers\Services;

use Illuminate\Support\Facades\DB;

/**
 * Calcula el score de un proveedor en base a:
 *  - Promedio de calificaciones recibidas (40%)
 *  - % compras entregadas a tiempo (30%) — placeholder, requiere tracking entrega
 *  - Antigüedad como proveedor (10%)
 *  - Frecuencia de compras últimos 12 meses (20%)
 *
 * Resultado: 0-100. Si nunca fue calificado y no hay compras: 0.
 */
final class SupplierScoreService
{
    public function calcular(int $supplierId): float
    {
        $promedioCalificacion = (float) DB::table('calificaciones_proveedor')
            ->where('proveedor_id', $supplierId)
            ->whereNull('deleted_at')
            ->avg('puntaje');                                 // 0-5

        $puntajeCalif = $promedioCalificacion > 0
            ? ($promedioCalificacion / 5) * 40                // hasta 40 pts
            : 0;

        // Antigüedad — 1 punto por año, max 10
        $proveedor = DB::table('proveedores')->where('id', $supplierId)->first();
        $antiguedad = 0;
        if ($proveedor && $proveedor->created_at) {
            $años = now()->diffInYears($proveedor->created_at);
            $antiguedad = min(10, $años);
        }

        // Frecuencia: 1 punto por compra últimos 12 meses, max 20
        $comprasUltimoAño = DB::table('compras')
            ->where('proveedor_id', $supplierId)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subYear())
            ->count();
        $frecuencia = min(20, $comprasUltimoAño);

        // Tiempo entrega — placeholder. Si no hay tracking, 30 pts default.
        $tiempoEntrega = 30;

        return round($puntajeCalif + $antiguedad + $frecuencia + $tiempoEntrega, 2);
    }

    public function recalcularYGuardar(int $supplierId): float
    {
        $score = $this->calcular($supplierId);
        DB::table('proveedores')
            ->where('id', $supplierId)
            ->update(['score_actual' => $score, 'updated_at' => now()]);
        return $score;
    }
}
