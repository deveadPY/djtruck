<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\Exceptions\InvalidInstallmentPlanException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * InstallmentGenerator — Genera planes de cuotas Francesa, Alemana o Manual.
 */
final class InstallmentGenerator
{
    /**
     * Genera y persiste las cuotas en BD.
     * Retorna array con los datos de cada cuota.
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
        ?Money          $refuerzoMonto = null,
    ): array {
        if ($numeroCuotas <= 0 || $numeroCuotas > 60) {
            throw new InvalidInstallmentPlanException(
                "Número de cuotas inválido: {$numeroCuotas}. Rango permitido: 1-60."
            );
        }

        $cuotas = match ($tipo) {
            InstallmentPlan::FRANCESA => $this->generarFrancesa($capital, $numeroCuotas, $tasaMensual, $refuerzoCada, $refuerzoMonto),
            InstallmentPlan::ALEMANA  => $this->generarAlemana($capital, $numeroCuotas, $tasaMensual, $refuerzoCada, $refuerzoMonto),
            InstallmentPlan::MANUAL   => throw new InvalidInstallmentPlanException(
                "Plan MANUAL requiere montos explícitos. Use generateManual()."
            ),
        };

        return $this->persistirCuotas(
            $cuotas, $planId, $ventaId, $tipo,
            $capital->currency->value, $fechaPrimeraCuota
        );
    }

    // ─────────────────────────────────────────────────────────
    // Sistema Francés: cuota fija = PMT
    // PMT = PV * [r(1+r)^n] / [(1+r)^n - 1]
    // ─────────────────────────────────────────────────────────
    private function generarFrancesa(Money $capital, int $n, float $tasaMensualPct, int $refuerzoCada = 0, ?Money $refuerzoMonto = null): array
    {
        $cuotas  = [];
        
        $hasRefuerzos = ($refuerzoCada > 0 && $refuerzoMonto !== null && $refuerzoMonto->amount > 0);
        $numRefuerzos = $hasRefuerzos ? intdiv($n, $refuerzoCada) : 0;
        
        $capitalAmortizable = $capital;
        if ($hasRefuerzos) {
            $totalRefuerzos = $refuerzoMonto->multiply($numRefuerzos);
            $capitalAmortizable = $capital->subtract($totalRefuerzos);
        }

        $pv      = $capitalAmortizable->amount;
        $r       = $tasaMensualPct / 100;
        $decimals = $capital->currency->decimals();

        $normalCuotas = [];
        if ($r == 0) {
            $cuotaFija = round($pv / $n, $decimals);
            for ($i = 1; $i <= $n; $i++) {
                $normalCuotas[] = [
                    'numero_base' => $i,
                    'capital'     => $i === $n ? round($pv - ($cuotaFija * ($n - 1)), $decimals) : $cuotaFija,
                    'interes'     => 0.0,
                ];
            }
        } else {
            $factor  = pow(1 + $r, $n);
            $pmt     = $pv * ($r * $factor) / ($factor - 1);
            $saldo   = $pv;

            for ($i = 1; $i <= $n; $i++) {
                $interes = round($saldo * $r, $decimals);
                $cap     = ($i === $n) ? round($saldo, $decimals) : round($pmt - $interes, $decimals);

                $normalCuotas[] = [
                    'numero_base' => $i,
                    'capital'     => $cap,
                    'interes'     => $interes,
                ];
                $saldo   -= $cap;
            }
        }

        $cuotaNumero = 0;
        foreach ($normalCuotas as $c) {
            $cuotaNumero++;
            $cuotas[] = [
                'numero'      => $cuotaNumero,
                'capital'     => $c['capital'],
                'interes'     => $c['interes'],
                'es_refuerzo' => false,
                'vencimiento_offset' => $c['numero_base'] - 1,
            ];

            if ($hasRefuerzos && $c['numero_base'] % $refuerzoCada === 0) {
                $cuotaNumero++;
                $cuotas[] = [
                    'numero'      => $cuotaNumero,
                    'capital'     => $refuerzoMonto->amount,
                    'interes'     => 0.0,
                    'es_refuerzo' => true,
                    'vencimiento_offset' => $c['numero_base'] - 1,
                ];
            }
        }

        return $cuotas;
    }

    // ─────────────────────────────────────────────────────────
    // Sistema Alemán: capital fijo, interés decreciente
    // ─────────────────────────────────────────────────────────
    private function generarAlemana(Money $capital, int $n, float $tasaMensualPct, int $refuerzoCada = 0, ?Money $refuerzoMonto = null): array
    {
        $cuotas     = [];
        
        $hasRefuerzos = ($refuerzoCada > 0 && $refuerzoMonto !== null && $refuerzoMonto->amount > 0);
        $numRefuerzos = $hasRefuerzos ? intdiv($n, $refuerzoCada) : 0;
        
        $capitalAmortizable = $capital;
        if ($hasRefuerzos) {
            $totalRefuerzos = $refuerzoMonto->multiply($numRefuerzos);
            $capitalAmortizable = $capital->subtract($totalRefuerzos);
        }

        $pv         = $capitalAmortizable->amount;
        $r          = $tasaMensualPct / 100;
        $decimals   = $capital->currency->decimals();
        
        $capFijo    = round($pv / $n, $decimals);
        $saldo      = $pv;

        $normalCuotas = [];
        for ($i = 1; $i <= $n; $i++) {
            $interes = round($saldo * $r, $decimals);
            $cap     = ($i === $n) ? round($saldo, $decimals) : $capFijo;

            $normalCuotas[] = [
                'numero_base' => $i,
                'capital'     => $cap,
                'interes'     => $interes,
            ];
            $saldo   -= $cap;
        }

        $cuotaNumero = 0;
        foreach ($normalCuotas as $c) {
            $cuotaNumero++;
            $cuotas[] = [
                'numero'      => $cuotaNumero,
                'capital'     => $c['capital'],
                'interes'     => $c['interes'],
                'es_refuerzo' => false,
                'vencimiento_offset' => $c['numero_base'] - 1,
            ];

            if ($hasRefuerzos && $c['numero_base'] % $refuerzoCada === 0) {
                $cuotaNumero++;
                $cuotas[] = [
                    'numero'      => $cuotaNumero,
                    'capital'     => $refuerzoMonto->amount,
                    'interes'     => 0.0,
                    'es_refuerzo' => true,
                    'vencimiento_offset' => $c['numero_base'] - 1,
                ];
            }
        }

        return $cuotas;
    }

    private function persistirCuotas(
        array  $cuotas,
        int    $planId,
        int    $ventaId,
        InstallmentPlan $tipo,
        string $moneda,
        string $fechaPrimera,
    ): array {
        $fecha       = Carbon::parse($fechaPrimera);
        $totalCuotas = count($cuotas);
        $registros   = [];

        foreach ($cuotas as $cuota) {
            $offset = $cuota['vencimiento_offset'] ?? ($cuota['numero'] - 1);
            $vencimiento = $fecha->copy()->addMonths($offset)->toDateString();

            $id = DB::table('cuotas')->insertGetId([
                'plan_cuotas_id'  => $planId,
                'venta_id'        => $ventaId,
                'numero_cuota'    => $cuota['numero'],
                'total_cuotas'    => $totalCuotas,
                'tipo_plan'       => $tipo->value,
                'moneda'          => $moneda,
                'capital'         => $cuota['capital'],
                'interes'         => $cuota['interes'],
                'fecha_vencimiento' => $vencimiento,
                'estado'          => 'PENDIENTE',
                'created_at'      => now(),
                'created_by'      => auth()->id() ?? 0,
            ]);

            $registros[] = [
                'id'              => $id,
                'numero'          => $cuota['numero'],
                'capital'         => $cuota['capital'],
                'interes'         => $cuota['interes'],
                'total'           => $cuota['capital'] + $cuota['interes'],
                'vencimiento'     => $vencimiento,
            ];
        }

        return $registros;
    }

    /**
     * Simula el plan sin persistir (para preview al cliente).
     */
    public function simulate(
        float           $capitalMonto,
        string          $monedaCode,
        int             $numeroCuotas,
        float           $tasaMensual,
        InstallmentPlan $tipo,
        string          $fechaPrimera,
        int             $refuerzoCada = 0,
        float           $refuerzoMonto = 0.0,
    ): array {
        $moneda  = \App\Domain\Shared\ValueObjects\Currency::from($monedaCode);
        $capital = new Money($capitalMonto, $moneda);
        $moneyRefuerzo = new Money($refuerzoMonto, $moneda);

        $cuotas = match ($tipo) {
            InstallmentPlan::FRANCESA => $this->generarFrancesa($capital, $numeroCuotas, $tasaMensual, $refuerzoCada, $moneyRefuerzo),
            InstallmentPlan::ALEMANA  => $this->generarAlemana($capital, $numeroCuotas, $tasaMensual, $refuerzoCada, $moneyRefuerzo),
            default => throw new InvalidInstallmentPlanException("Tipo no simulable: {$tipo->value}"),
        };

        $fecha        = Carbon::parse($fechaPrimera);
        $totalCapital = 0;
        $totalInteres = 0;
        $resultado    = [];

        foreach ($cuotas as $c) {
            $offset         = $c['vencimiento_offset'] ?? ($c['numero'] - 1);
            $vencimiento    = $fecha->copy()->addMonths($offset)->toDateString();
            $totalCapital  += $c['capital'];
            $totalInteres  += $c['interes'];
            $resultado[]    = [
                'numero'     => $c['numero'],
                'capital'    => $c['capital'],
                'interes'    => $c['interes'],
                'total'      => $c['capital'] + $c['interes'],
                'vencimiento'=> $vencimiento,
            ];
        }

        return [
            'cuotas'        => $resultado,
            'resumen' => [
                'capital_total'  => round($totalCapital, $moneda->decimals()),
                'interes_total'  => round($totalInteres, $moneda->decimals()),
                'costo_total'    => round($totalCapital + $totalInteres, $moneda->decimals()),
                'moneda'         => $monedaCode,
            ],
        ];
    }
}
