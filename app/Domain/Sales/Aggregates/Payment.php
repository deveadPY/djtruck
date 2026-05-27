<?php

declare(strict_types=1);

namespace App\Domain\Sales\Aggregates;

use App\Domain\Sales\ValueObjects\PaymentType;
use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;

final readonly class Payment
{
    public function __construct(
        public PaymentType $type,
        public Money $amount,
        public ?int $tradeInVehicleId = null,
        public ?string $reference = null,
        public ?int $planCuotasId = null,
    ) {
        if (!$amount->isPositive()) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor a cero.');
        }

        if ($type->requiresVehicle() && $tradeInVehicleId === null) {
            throw new InvalidArgumentException('Pago tipo VEHICULO_CANJE requiere tradeInVehicleId.');
        }

        if ($type->requiresInstallmentPlan() && $planCuotasId === null) {
            throw new InvalidArgumentException('Pago tipo PLAN_CUOTAS requiere planCuotasId.');
        }
    }

    public function isCashPayment(): bool
    {
        return in_array($this->type, [
            PaymentType::EFECTIVO,
            PaymentType::TRANSFERENCIA,
            PaymentType::CHEQUE,
            PaymentType::TARJETA,
        ], true);
    }

    public function isTradeIn(): bool
    {
        return $this->type === PaymentType::VEHICULO_CANJE;
    }

    public function isInstallmentPlan(): bool
    {
        return $this->type === PaymentType::PLAN_CUOTAS;
    }
}
