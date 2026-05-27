<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Sales\ValueObjects\ImputationBreakdown;

/**
 * Reglas de imputación de pago.
 *
 * Orden de prelación al recibir un pago de cuota(s):
 *   1. Interés moratorio (mora) — se paga primero
 *   2. Interés ordinario — se paga segundo
 *   3. Capital — se paga último
 *
 * Si el monto pagado no cubre el total, el remanente queda como
 * saldo insoluto sobre el capital (pago parcial).
 */
final class PaymentImputationService
{
    /**
     * @param array $cuotasData Cada elemento debe tener:
     *   - capital: float
     *   - interes: float
     *   - interes_mora: float
     *   - monto_pagado: float (lo ya pagado previamente)
     *
     * @return ImputationBreakdown Cómo se distribuye el monto pagado.
     */
    public function allocate(float $montoPagado, array $cuotasData): ImputationBreakdown
    {
        $decimals = 4;

        // Totales pendientes (restando lo ya pagado)
        $capitalPendiente = 0;
        $interesPendiente = 0;
        $moraPendiente    = 0;

        foreach ($cuotasData as $c) {
            $pagadoPrevio = (float) ($c['monto_pagado'] ?? 0);
            $capitalPendiente += max(0, (float) $c['capital'] - $pagadoPrevio);
            $interesPendiente += (float) ($c['interes'] ?? 0);
            $moraPendiente    += (float) ($c['interes_mora'] ?? 0);
        }

        $restante = $montoPagado;

        // 1. Imputar a mora
        $moraAllocated = min($restante, $moraPendiente);
        $restante -= $moraAllocated;

        // 2. Imputar a interés ordinario
        $interesAllocated = min($restante, $interesPendiente);
        $restante -= $interesAllocated;

        // 3. Imputar a capital
        $capitalAllocated = min($restante, $capitalPendiente);
        $restante -= $capitalAllocated;

        return new ImputationBreakdown(
            capital:           round($capitalAllocated, $decimals),
            interes:           round($interesAllocated, $decimals),
            mora:              round($moraAllocated, $decimals),
            descuentoAnticipo: 0,
            total:             round($montoPagado - $restante, $decimals),
        );
    }

    /**
     * Calcula el total pendiente para una o más cuotas.
     */
    public function totalPendiente(array $cuotasData): float
    {
        $total = 0;
        foreach ($cuotasData as $c) {
            $pagadoPrevio = (float) ($c['monto_pagado'] ?? 0);
            $capitalPend  = max(0, (float) $c['capital'] - $pagadoPrevio);
            $total += $capitalPend + (float) ($c['interes'] ?? 0) + (float) ($c['interes_mora'] ?? 0);
        }
        return round($total, 4);
    }
}
