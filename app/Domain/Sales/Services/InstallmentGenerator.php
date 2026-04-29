<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\Exceptions\InvalidInstallmentPlanException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * InstallmentGenerator — Genera planes de cuotas Francesa, Alemana o Manual.
 *
 * Todos los métodos públicos persisten en BD y devuelven los registros creados.
 * Para preview sin persistir, usar simulate().
 */
final class InstallmentGenerator
{
    /**
     * Genera cuotas automáticas (Francesa o Alemana).
     * Soporta cuotas de refuerzo intercaladas cada $refuerzoCada meses.
     */
    public function generate(
        int             $planId,
        int             $ventaId,
        InstallmentPlan $tipo,
        Money           $capital,
        int             $numeroCuotas,
        float           $tasaMensual,
        string          $fechaPrimeraCuota,
        int             $refuerzoCada = 0,
        float           $refuerzoMonto = 0.0,
    ): array {
        if ($numeroCuotas <= 0 || $numeroCuotas > 120) {
            throw new InvalidInstallmentPlanException(
                "Número de cuotas inválido: {$numeroCuotas}. Rango permitido: 1-120."
            );
        }

        // Descontar refuerzos del capital regular para que las cuotas normales
        // se calculen sobre el monto correcto.
        $capitalEfectivo = $capital->amount;
        if ($refuerzoCada > 0 && $refuerzoMonto > 0) {
            $numRefuerzos    = intdiv($numeroCuotas, $refuerzoCada);
            $capitalEfectivo = max(0.0, $capitalEfectivo - ($numRefuerzos * $refuerzoMonto));
        }
        $capitalParaPlan = new Money($capitalEfectivo, $capital->currency);

        $cuotasBase = match ($tipo) {
            InstallmentPlan::FRANCESA => $this->generarFrancesa($capitalParaPlan, $numeroCuotas, $tasaMensual),
            InstallmentPlan::ALEMANA  => $this->generarAlemana($capitalParaPlan, $numeroCuotas, $tasaMensual),
            InstallmentPlan::MANUAL   => throw new InvalidInstallmentPlanException(
                "Plan MANUAL requiere montos explícitos. Use generateManual()."
            ),
        };

        $rows = $this->aplicarFechasYRefuerzos(
            $cuotasBase,
            $fechaPrimeraCuota,
            $capital->currency,
            $tipo->value,
            $refuerzoCada,
            $refuerzoMonto,
        );

        return $this->persistirCuotas($rows, $planId, $ventaId, $capital->currency->value);
    }

    /**
     * Genera cuotas desde un grid manual (fechas y montos libres por fila).
     */
    public function generateManual(
        int    $planId,
        int    $ventaId,
        string $moneda,
        array  $cuotasManual,
    ): array {
        $rows   = [];
        $numero = 1;

        foreach ($cuotasManual as $row) {
            $monto = floatval($row['monto'] ?? 0);
            if ($monto <= 0) {
                continue;
            }

            $rows[] = [
                'numero'      => $numero,
                'capital'     => round($monto, 4),
                'interes'     => 0.0,
                'tipo_plan'   => 'MANUAL',
                'vencimiento' => $row['fecha'] ?? Carbon::now()->addMonths($numero)->toDateString(),
            ];
            $numero++;
        }

        return $this->persistirCuotas($rows, $planId, $ventaId, $moneda);
    }

    /**
     * Simula el plan sin persistir (para preview/cotización al cliente).
     */
    public function simulate(
        float           $capitalMonto,
        string          $monedaCode,
        int             $numeroCuotas,
        float           $tasaMensual,
        InstallmentPlan $tipo,
        string          $fechaPrimera,
    ): array {
        $moneda  = Currency::from($monedaCode);
        $capital = new Money($capitalMonto, $moneda);

        $cuotas = match ($tipo) {
            InstallmentPlan::FRANCESA => $this->generarFrancesa($capital, $numeroCuotas, $tasaMensual),
            InstallmentPlan::ALEMANA  => $this->generarAlemana($capital, $numeroCuotas, $tasaMensual),
            default                   => throw new InvalidInstallmentPlanException(
                "Tipo no simulable en modo automático: {$tipo->value}"
            ),
        };

        $fecha        = Carbon::parse($fechaPrimera);
        $totalCapital = 0.0;
        $totalInteres = 0.0;
        $resultado    = [];

        foreach ($cuotas as $c) {
            $vencimiento   = $fecha->copy()->addMonths($c['numero'] - 1)->toDateString();
            $totalCapital += $c['capital'];
            $totalInteres += $c['interes'];
            $resultado[]   = [
                'numero'      => $c['numero'],
                'capital'     => $c['capital'],
                'interes'     => $c['interes'],
                'total'       => $c['capital'] + $c['interes'],
                'vencimiento' => $vencimiento,
            ];
        }

        return [
            'cuotas'  => $resultado,
            'resumen' => [
                'capital_total' => round($totalCapital, $moneda->decimals()),
                'interes_total' => round($totalInteres, $moneda->decimals()),
                'costo_total'   => round($totalCapital + $totalInteres, $moneda->decimals()),
                'moneda'        => $monedaCode,
            ],
        ];
    }

    // ── Algoritmos financieros puros ──────────────────────────────────────────

    // Sistema Francés: cuota fija PMT = PV · r(1+r)^n / [(1+r)^n − 1]
    private function generarFrancesa(Money $capital, int $n, float $tasaMensualPct): array
    {
        $cuotas = [];
        $pv     = $capital->amount;
        $r      = $tasaMensualPct / 100;
        $dec    = $capital->currency->decimals();

        if ($r == 0) {
            $cuotaFija = round($pv / $n, $dec);
            for ($i = 1; $i <= $n; $i++) {
                $cuotas[] = ['numero' => $i, 'capital' => $cuotaFija, 'interes' => 0.0];
            }
            return $cuotas;
        }

        $factor = pow(1 + $r, $n);
        $pmt    = $pv * ($r * $factor) / ($factor - 1);
        $saldo  = $pv;

        for ($i = 1; $i <= $n; $i++) {
            $interes = round($saldo * $r, $dec);
            $cap     = ($i === $n)
                ? round($saldo, $dec)
                : round($pmt - $interes, $dec);

            $cuotas[] = ['numero' => $i, 'capital' => $cap, 'interes' => $interes];
            $saldo   -= $cap;
        }

        return $cuotas;
    }

    // Sistema Alemán: capital fijo, interés decreciente sobre saldo
    private function generarAlemana(Money $capital, int $n, float $tasaMensualPct): array
    {
        $cuotas  = [];
        $pv      = $capital->amount;
        $r       = $tasaMensualPct / 100;
        $dec     = $capital->currency->decimals();
        $capFijo = round($pv / $n, $dec);
        $saldo   = $pv;

        for ($i = 1; $i <= $n; $i++) {
            $interes = round($saldo * $r, $dec);
            $cap     = ($i === $n) ? round($saldo, $dec) : $capFijo;

            $cuotas[] = ['numero' => $i, 'capital' => $cap, 'interes' => $interes];
            $saldo   -= $cap;
        }

        return $cuotas;
    }

    // ── Helpers internos ──────────────────────────────────────────────────────

    /**
     * Asigna fechas de vencimiento a las cuotas base e intercala cuotas de
     * refuerzo después de cada N-ésima cuota regular.
     */
    private function aplicarFechasYRefuerzos(
        array    $cuotasBase,
        string   $fechaPrimera,
        Currency $currency,
        string   $tipoPlan,
        int      $refuerzoCada,
        float    $refuerzoMonto,
    ): array {
        $fecha       = Carbon::parse($fechaPrimera);
        $rows        = [];
        $cuotaNumero = 0;

        foreach ($cuotasBase as $c) {
            $cuotaNumero++;
            $fechaCuota = $fecha->copy()->addMonths($c['numero'] - 1)->toDateString();

            $rows[] = [
                'numero'      => $cuotaNumero,
                'capital'     => $c['capital'],
                'interes'     => $c['interes'],
                'tipo_plan'   => $tipoPlan,
                'vencimiento' => $fechaCuota,
            ];

            if ($refuerzoCada > 0 && $refuerzoMonto > 0 && $c['numero'] % $refuerzoCada === 0) {
                $cuotaNumero++;
                $rows[] = [
                    'numero'      => $cuotaNumero,
                    'capital'     => round($refuerzoMonto, $currency->decimals()),
                    'interes'     => 0.0,
                    'tipo_plan'   => $tipoPlan,
                    'vencimiento' => $fechaCuota,
                ];
            }
        }

        return $rows;
    }

    /**
     * Persiste todas las filas en un único bulk insert y actualiza el plan.
     */
    private function persistirCuotas(array $rows, int $planId, int $ventaId, string $moneda): array
    {
        if (empty($rows)) {
            return [];
        }

        $total    = count($rows);
        $inserts  = [];
        $resultado = [];
        $now      = now();
        $userId   = auth()->id() ?? 0;

        foreach ($rows as $row) {
            $inserts[] = [
                'plan_cuotas_id'    => $planId,
                'venta_id'          => $ventaId,
                'numero_cuota'      => $row['numero'],
                'total_cuotas'      => $total,
                'tipo_plan'         => $row['tipo_plan'],
                'moneda'            => $moneda,
                'capital'           => $row['capital'],
                'interes'           => $row['interes'],
                'fecha_vencimiento' => $row['vencimiento'],
                'estado'            => 'PENDIENTE',
                'monto_pagado'      => 0,
                'interes_mora'      => 0,
                'created_by'        => $userId,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            $resultado[] = [
                'numero'      => $row['numero'],
                'capital'     => $row['capital'],
                'interes'     => $row['interes'],
                'total'       => $row['capital'] + $row['interes'],
                'vencimiento' => $row['vencimiento'],
            ];
        }

        DB::table('cuotas')->insert($inserts);
        DB::table('planes_cuotas')->where('id', $planId)->update(['numero_cuotas' => $total]);

        return $resultado;
    }
}
