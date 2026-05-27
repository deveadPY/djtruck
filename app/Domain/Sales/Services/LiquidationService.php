<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use Carbon\Carbon;

/**
 * Servicio de liquidación de planes de cuotas.
 *
 * Calcula el saldo para cancelar un plan completo, aplicando
 * opcionalmente un descuento sobre el interés NO DEVENGADO
 * (interés futuro que el cliente "se ahorra" por pagar antes).
 *
 * El descuento por liquidación NO aplica sobre:
 *  - Capital pendiente
 *  - Intereses ya devengados (cuotas vencidas)
 *  - Intereses moratorios
 *
 * Solo aplica sobre el interés de cuotas FUTURAS (PENDIENTES
 * con fecha de vencimiento > hoy).
 */
final class LiquidationService
{
    /**
     * Calcula el desglose de liquidación de un plan.
     *
     * @param array $cuotas Array de cuotas del plan (cada una con:
     *   capital, interes, interes_mora, monto_pagado, estado, fecha_vencimiento)
     * @param float $porcentajeDescuento % de descuento sobre interés no devengado
     * @param string $fechaLiquidacion Fecha en que se liquida
     *
     * @return array{capital_pendiente: float, interes_devengado: float, interes_no_devengado: float, mora_total: float, descuento_aplicado: float, total_liquidacion: float, detalle: array}
     */
    public function calcular(
        array  $cuotas,
        float  $porcentajeDescuento,
        string $fechaLiquidacion,
    ): array {
        $hoy = Carbon::parse($fechaLiquidacion);

        $capitalPendiente     = 0;
        $interesDevengado     = 0;
        $interesNoDevengado   = 0;
        $moraTotal            = 0;
        $detalle              = [];

        foreach ($cuotas as $c) {
            // Solo cuotas no pagadas
            if (in_array($c['estado'] ?? '', ['PAGADA', 'ANULADA'])) {
                continue;
            }

            $cap       = (float) $c['capital'];
            $int       = (float) ($c['interes'] ?? 0);
            $mora      = (float) ($c['interes_mora'] ?? 0);
            $pagado    = (float) ($c['monto_pagado'] ?? 0);
            $capPend   = max(0, $cap - $pagado);
            $venc      = Carbon::parse($c['fecha_vencimiento']);

            $capitalPendiente += $capPend;
            $moraTotal        += $mora;

            if ($venc->lte($hoy)) {
                // Vencida o vence hoy: interés ya devengado
                $interesDevengado += $int;
            } else {
                // Futura: interés no devengado (sujeto a descuento)
                $interesNoDevengado += $int;
            }

            $detalle[] = [
                'cuota_id'       => $c['id'] ?? null,
                'numero'         => $c['numero_cuota'] ?? 0,
                'capital'        => $capPend,
                'interes'        => $int,
                'mora'           => $mora,
                'vencimiento'    => $c['fecha_vencimiento'],
                'tipo_interes'   => $venc->lte($hoy) ? 'devengado' : 'no_devengado',
            ];
        }

        $descuentoAplicado = 0;
        if ($porcentajeDescuento > 0 && $interesNoDevengado > 0) {
            $descuentoAplicado = round($interesNoDevengado * ($porcentajeDescuento / 100), 4);
        }

        $totalLiquidacion = $capitalPendiente
            + $interesDevengado
            + $interesNoDevengado
            + $moraTotal
            - $descuentoAplicado;

        return [
            'capital_pendiente'      => round($capitalPendiente, 4),
            'interes_devengado'      => round($interesDevengado, 4),
            'interes_no_devengado'   => round($interesNoDevengado, 4),
            'mora_total'             => round($moraTotal, 4),
            'descuento_aplicado'     => $descuentoAplicado,
            'total_liquidacion'      => round(max(0, $totalLiquidacion), 4),
            'porcentaje_aplicado'    => $porcentajeDescuento,
            'detalle'                => $detalle,
        ];
    }
}
