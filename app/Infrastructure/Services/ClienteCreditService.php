<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Illuminate\Support\Facades\DB;

/**
 * ClienteCreditService — Cálculo de deuda activa y crédito disponible.
 *
 * Centraliza la query de saldo_deudor que antes se duplicaba en
 * VentaWebController (x2), ClienteWebController (x2).
 */
final class ClienteCreditService
{
    /**
     * Suma de capital + interés + mora − monto_pagado de cuotas no canceladas.
     */
    public function saldoDeudorUsd(int $clienteId): float
    {
        return (float) DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->where('planes_cuotas.cliente_id', $clienteId)
            ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->whereNull('cuotas.deleted_at')
            ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));
    }

    /**
     * Crédito disponible = línea − deuda activa. Nunca negativo.
     */
    public function creditoDisponibleUsd(int $clienteId, float $lineaCredito): float
    {
        return max(0.0, $lineaCredito - $this->saldoDeudorUsd($clienteId));
    }
}
