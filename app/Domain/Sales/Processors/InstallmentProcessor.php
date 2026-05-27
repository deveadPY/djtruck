<?php

declare(strict_types=1);

namespace App\Domain\Sales\Processors;

use App\Domain\Sales\Repositories\InstallmentRepositoryInterface;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InstallmentProcessor
{
    public function __construct(
        private readonly InstallmentGenerator $installmentGenerator,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly InstallmentRepositoryInterface $installmentRepository
    ) {}

    public function process(
        int $ventaId,
        int $clienteId,
        string $monedaVenta,
        string $tipoPlan,
        float $capitalTotalUsd,
        int $numeroCuotas,
        float $tasaInteresMensual,
        string $fechaPrimeraCuota,
        array $cuotasManual,
        int $refuerzoCada,
        float $refuerzoMonto,
        string $fechaVenta
    ): int {
        $planId = $this->createPlan(
            $ventaId,
            $clienteId,
            $monedaVenta,
            $tipoPlan,
            $capitalTotalUsd,
            $numeroCuotas,
            $tasaInteresMensual,
            $fechaPrimeraCuota
        );

        if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
            $this->generateManualInstallments($planId, $ventaId, $monedaVenta, $cuotasManual);
        } else {
            $this->generateAutomaticInstallments(
                $planId,
                $ventaId,
                $monedaVenta,
                $tipoPlan,
                $capitalTotalUsd,
                $numeroCuotas,
                $tasaInteresMensual,
                $fechaPrimeraCuota,
                $refuerzoCada,
                $refuerzoMonto
            );
        }

        $this->registerPlanPaymentDetail($ventaId, $monedaVenta, $capitalTotalUsd, $planId, $fechaVenta);

        return $planId;
    }

    private function createPlan(
        int $ventaId,
        int $clienteId,
        string $monedaVenta,
        string $tipoPlan,
        float $capitalTotalUsd,
        int $numeroCuotas,
        float $tasaInteresMensual,
        string $fechaPrimeraCuota
    ): int {
        return $this->saleRepository->addInstallmentPlan($ventaId, [
            'cliente_id' => $clienteId,
            'tipo_plan' => $tipoPlan,
            'moneda' => $monedaVenta,
            'capital_total' => $capitalTotalUsd,
            'capital_total_usd' => $capitalTotalUsd,
            'numero_cuotas' => $numeroCuotas,
            'tasa_interes_mensual' => $tasaInteresMensual,
            'fecha_primera_cuota' => $fechaPrimeraCuota,
            'estado' => 'ACTIVO',
        ]);
    }

    private function generateManualInstallments(int $planId, int $ventaId, string $moneda, array $cuotasManual): void
    {
        $cuotas = [];
        $i = 1;
        $totalCuotas = count(array_filter($cuotasManual, fn($c) => (float) ($c['monto'] ?? 0) > 0));

        foreach ($cuotasManual as $row) {
            $monto = (float) ($row['monto'] ?? 0);
            if ($monto <= 0) {
                continue;
            }

            $cuotas[] = [
                'plan_cuotas_id' => $planId,
                'venta_id' => $ventaId,
                'numero_cuota' => $i,
                'total_cuotas' => $totalCuotas,
                'tipo_plan' => 'MANUAL',
                'moneda' => $moneda,
                'capital' => round($monto, 4),
                'interes' => 0,
                'fecha_vencimiento' => $row['fecha'] ?? now()->addMonths($i)->toDateString(),
                'estado' => 'PENDIENTE',
                'monto_pagado' => 0,
                'interes_mora' => 0,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $i++;
        }

        $this->installmentRepository->insertMany($cuotas);
    }

    private function generateAutomaticInstallments(
        int $planId,
        int $ventaId,
        string $monedaVenta,
        string $tipoPlan,
        float $capitalTotalUsd,
        int $numeroCuotas,
        float $tasaInteresMensual,
        string $fechaPrimeraCuota,
        int $refuerzoCada,
        float $refuerzoMonto
    ): void {
        $monedaEnum = Currency::from($monedaVenta);
        $capitalMoney = new Money($capitalTotalUsd, $monedaEnum);
        $tipoPlanEnum = InstallmentPlan::from($tipoPlan);
        $refuerzoMoney = new Money($refuerzoMonto, $monedaEnum);

        $generatedCuotas = $this->installmentGenerator->generate(
            $planId,
            $ventaId,
            $tipoPlanEnum,
            $capitalMoney,
            $numeroCuotas,
            $tasaInteresMensual,
            $fechaPrimeraCuota,
            $refuerzoCada,
            $refuerzoMoney
        );

        DB::table('planes_cuotas')
            ->where('id', $planId)
            ->update(['numero_cuotas' => count($generatedCuotas)]);
    }

    private function registerPlanPaymentDetail(
        int $ventaId,
        string $moneda,
        float $capitalTotalUsd,
        int $planId,
        string $fechaVenta
    ): void {
        $this->saleRepository->addPayment($ventaId, [
            'tipo_pago' => 'PLAN_CUOTAS',
            'moneda' => $moneda,
            'monto_moneda' => $capitalTotalUsd,
            'monto_usd' => $capitalTotalUsd,
            'tasa_cambio' => 1,
            'plan_cuotas_id' => $planId,
            'fecha_pago' => $fechaVenta,
            'created_by' => Auth::id(),
        ]);
    }

    public function revert(int $ventaId): void
    {
        $this->saleRepository->removePlan($ventaId);
    }
}
