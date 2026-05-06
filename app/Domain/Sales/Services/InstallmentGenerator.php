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
    ): array {
        if ($numeroCuotas <= 0 || $numeroCuotas > 60) {
            throw new InvalidInstallmentPlanException(
                "Número de cuotas inválido: {$numeroCuotas}. Rango permitido: 1-60."
            );
        }

        $cuotas = match ($tipo) {
            InstallmentPlan::FRANCESA => $this->generarFrancesa($capital, $numeroCuotas, $tasaMensual),
            InstallmentPlan::ALEMANA  => $this->generarAlemana($capital, $numeroCuotas, $tasaMensual),
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
    private function generarFrancesa(Money $capital, int $n, float $tasaMensualPct): array
    {
        $cuotas  = [];
        $pv      = $capital->amount;
        $r       = $tasaMensualPct / 100;

        if ($r == 0) {
            // Sin interés: división simple
            $cuotaFija = round($pv / $n, $capital->currency->decimals());
            for ($i = 1; $i <= $n; $i++) {
                $cuotas[] = [
                    'numero'   => $i,
                    'capital'  => $cuotaFija,
                    'interes'  => 0.0,
                ];
            }
            return $cuotas;
        }

        $factor  = pow(1 + $r, $n);
        $pmt     = $pv * ($r * $factor) / ($factor - 1);
        $saldo   = $pv;

        for ($i = 1; $i <= $n; $i++) {
            $interes = round($saldo * $r, $capital->currency->decimals());
            $cap     = round($pmt - $interes, $capital->currency->decimals());

            // Última cuota: absorber diferencia de redondeo
            if ($i === $n) {
                $cap = round($saldo, $capital->currency->decimals());
            }

            $cuotas[] = ['numero' => $i, 'capital' => $cap, 'interes' => $interes];
            $saldo   -= $cap;
        }

        return $cuotas;
    }

    // ─────────────────────────────────────────────────────────
    // Sistema Alemán: capital fijo, interés decreciente
    // ─────────────────────────────────────────────────────────
    private function generarAlemana(Money $capital, int $n, float $tasaMensualPct): array
    {
        $cuotas     = [];
        $pv         = $capital->amount;
        $r          = $tasaMensualPct / 100;
        $capFijo    = round($pv / $n, $capital->currency->decimals());
        $saldo      = $pv;

        for ($i = 1; $i <= $n; $i++) {
            $interes = round($saldo * $r, $capital->currency->decimals());
            $cap     = ($i === $n) ? round($saldo, $capital->currency->decimals()) : $capFijo;

            $cuotas[] = ['numero' => $i, 'capital' => $cap, 'interes' => $interes];
            $saldo   -= $cap;
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
            $vencimiento = $fecha->copy()->addMonths($cuota['numero'] - 1)->toDateString();

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
    ): array {
        $moneda  = \App\Domain\Shared\ValueObjects\Currency::from($monedaCode);
        $capital = new Money($capitalMonto, $moneda);

        $cuotas = match ($tipo) {
            InstallmentPlan::FRANCESA => $this->generarFrancesa($capital, $numeroCuotas, $tasaMensual),
            InstallmentPlan::ALEMANA  => $this->generarAlemana($capital, $numeroCuotas, $tasaMensual),
            default => throw new InvalidInstallmentPlanException("Tipo no simulable: {$tipo->value}"),
        };

        $fecha        = Carbon::parse($fechaPrimera);
        $totalCapital = 0;
        $totalInteres = 0;
        $resultado    = [];

        foreach ($cuotas as $c) {
            $vencimiento    = $fecha->copy()->addMonths($c['numero'] - 1)->toDateString();
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
