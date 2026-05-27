<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de devengo de interés moratorio diario.
 *
 * Cada día que una cuota está vencida, se acumula un interés
 * de mora calculado como:
 *
 *   mora_diaria = (capital + interes) * (tasa_mora_diaria_pct / 100)
 *
 * La tasa es configurable en config/erp.php → installments.tasa_mora_diaria_pct
 */
final class MoratoriumService
{
    /**
     * Calcula el interés moratorio diario para una cuota.
     */
    public function moraDiaria(float $capital, float $interes, ?float $tasaDiariaPct = null): float
    {
        $tasa = $tasaDiariaPct ?? (float) config('erp.installments.tasa_mora_diaria_pct', 0.1);
        $base = $capital + $interes;
        return round($base * ($tasa / 100), 4);
    }

    /**
     * Acumula interés moratorio a TODAS las cuotas vencidas.
     * Debe ejecutarse diariamente (via scheduler).
     *
     * @return array{procesadas: int, total_mora_acumulada: float}
     */
    public function acumularMoraDiaria(): array
    {
        $tasa = (float) config('erp.installments.tasa_mora_diaria_pct', 0.1);
        $hoy  = now()->toDateString();

        $vencidas = DB::table('cuotas')
            ->whereIn('estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA'])
            ->where('fecha_vencimiento', '<', $hoy)
            ->whereNull('deleted_at')
            ->get();

        $totalAcumulado  = 0;
        $procesadas      = 0;

        foreach ($vencidas as $cuota) {
            $capital  = (float) $cuota->capital;
            $interes  = (float) $cuota->interes;
            $pagado   = (float) ($cuota->monto_pagado ?? 0);

            // Solo devengar mora sobre el saldo de capital pendiente
            $saldoCapital = max(0, $capital - $pagado);
            if ($saldoCapital <= 0) {
                continue;
            }

            $moraHoy = $this->moraDiaria($saldoCapital, $interes, $tasa);

            DB::table('cuotas')->where('id', $cuota->id)->update([
                'interes_mora' => round((float) $cuota->interes_mora + $moraHoy, 4),
                'estado'       => $cuota->estado === 'PENDIENTE' ? 'VENCIDA' : $cuota->estado,
                'updated_at'   => now(),
            ]);

            $totalAcumulado += $moraHoy;
            $procesadas++;
        }

        return [
            'procesadas'          => $procesadas,
            'total_mora_acumulada' => round($totalAcumulado, 4),
        ];
    }
}
