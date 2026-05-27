<?php

declare(strict_types=1);

namespace App\Domain\Parts\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio que registra TODOS los movimientos de stock en el kardex.
 * Cada movimiento queda con el saldo resultante para auditoría completa.
 */
final class StockMovementService
{
    public const TIPO_ENTRADA          = 'ENTRADA';
    public const TIPO_SALIDA           = 'SALIDA';
    public const TIPO_AJUSTE           = 'AJUSTE';
    public const TIPO_RESERVA          = 'RESERVA';
    public const TIPO_RELEASE_RESERVA  = 'RELEASE_RESERVA';

    /**
     * Registra una entrada en el kardex.
     * Caller es responsable del DB::transaction() y la actualización del stock_repuestos.
     */
    public function registrar(
        int     $partId,
        string  $tipo,
        string  $motivo,
        float   $cantidad,
        float   $saldoResultante,
        ?float  $costoUnitarioUsd = null,
        ?float  $costoPromedioResultante = null,
        ?string $referenciaType = null,
        ?int    $referenciaId   = null,
        ?string $observaciones  = null,
    ): int {
        return DB::table('kardex_repuestos')->insertGetId([
            'repuesto_id'                 => $partId,
            'tipo'                        => $tipo,
            'motivo'                      => $motivo,
            'cantidad'                    => round($cantidad, 3),
            'costo_unitario_usd'          => $costoUnitarioUsd !== null ? round($costoUnitarioUsd, 4) : null,
            'saldo_resultante'            => round($saldoResultante, 3),
            'costo_promedio_resultante'   => $costoPromedioResultante !== null ? round($costoPromedioResultante, 4) : null,
            'referencia_type'             => $referenciaType,
            'referencia_id'               => $referenciaId,
            'observaciones'               => $observaciones,
            'created_by'                  => Auth::id(),
            'created_at'                  => now(),
        ]);
    }
}
