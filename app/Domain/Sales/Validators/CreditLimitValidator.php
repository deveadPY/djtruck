<?php

declare(strict_types=1);

namespace App\Domain\Sales\Validators;

use App\Domain\Sales\Exceptions\InsufficientCreditLimitException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditLimitValidator
{
    public function validate(int $clienteId, float $capitalRequeridoUsd): void
    {
        $cliente = DB::table('clientes')->where('id', $clienteId)->first();

        if (!$cliente) {
            throw new RuntimeException("Cliente con ID {$clienteId} no encontrado.");
        }

        $lineaCredito = (float) ($cliente->linea_credito_usd ?? 0);

        if ($lineaCredito <= 0) {
            return;
        }

        $creditoDisponible = $this->getAvailableCredit($clienteId, $lineaCredito);

        if ($capitalRequeridoUsd > $creditoDisponible) {
            throw new InsufficientCreditLimitException($clienteId, $capitalRequeridoUsd, $creditoDisponible);
        }
    }

    public function getAvailableCredit(int $clienteId, ?float $lineaCredito = null): float
    {
        if ($lineaCredito === null) {
            $cliente = DB::table('clientes')->where('id', $clienteId)->first();
            $lineaCredito = (float) ($cliente->linea_credito_usd ?? 0);
        }

        $saldoDeudor = DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->where('planes_cuotas.cliente_id', $clienteId)
            ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->whereNull('cuotas.deleted_at')
            ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));

        return $lineaCredito - (float) $saldoDeudor;
    }
}
