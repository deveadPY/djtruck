<?php

declare(strict_types=1);

namespace App\Domain\Sales\Validators;

use App\Domain\Sales\Exceptions\InvalidInstallmentConfigException;
use App\Domain\Sales\ValueObjects\InstallmentPlan;

class InstallmentValidator
{
    private const MIN_INSTALLMENTS = 1;
    private const MAX_INSTALLMENTS = 60;

    public function validate(string $tipoPlan, int $numeroCuotas, ?array $cuotasManual = null): void
    {
        $this->validatePlanType($tipoPlan);
        $this->validateInstallmentCount($numeroCuotas);

        if ($tipoPlan === 'MANUAL') {
            $this->validateManualInstallments($cuotasManual ?? []);
        }
    }

    public function validatePlanType(string $tipoPlan): void
    {
        if (InstallmentPlan::tryFrom($tipoPlan) === null) {
            throw InvalidInstallmentConfigException::invalidType($tipoPlan);
        }
    }

    public function validateInstallmentCount(int $numeroCuotas): void
    {
        if ($numeroCuotas < self::MIN_INSTALLMENTS || $numeroCuotas > self::MAX_INSTALLMENTS) {
            throw InvalidInstallmentConfigException::invalidCount(
                $numeroCuotas,
                self::MIN_INSTALLMENTS,
                self::MAX_INSTALLMENTS
            );
        }
    }

    public function validateManualInstallments(array $cuotasManual): void
    {
        if (empty($cuotasManual)) {
            throw InvalidInstallmentConfigException::manualRequiresInstallments();
        }

        foreach ($cuotasManual as $i => $cuota) {
            $monto = (float) ($cuota['monto'] ?? 0);
            if ($monto <= 0) {
                throw InvalidInstallmentConfigException::invalidInstallmentAmount($i);
            }
        }
    }

    public function validateFirstInstallmentDate(?string $fechaPrimeraCuota, string $fechaVenta): void
    {
        if ($fechaPrimeraCuota === null) {
            return;
        }

        if (strtotime($fechaPrimeraCuota) < strtotime($fechaVenta)) {
            throw InvalidInstallmentConfigException::invalidFirstDate($fechaPrimeraCuota, $fechaVenta);
        }
    }
}
