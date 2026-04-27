<?php

namespace App\Domain\Sales\ValueObjects;

enum PaymentType: string
{
    case EFECTIVO        = 'EFECTIVO';
    case TRANSFERENCIA   = 'TRANSFERENCIA';
    case CHEQUE          = 'CHEQUE';
    case VEHICULO_CANJE  = 'VEHICULO_CANJE';
    case PLAN_CUOTAS     = 'PLAN_CUOTAS';
    case TARJETA         = 'TARJETA';

    public function label(): string
    {
        return match ($this) {
            self::EFECTIVO       => 'Efectivo',
            self::TRANSFERENCIA  => 'Transferencia Bancaria',
            self::CHEQUE         => 'Cheque',
            self::VEHICULO_CANJE => 'Vehículo Canje/Toma',
            self::PLAN_CUOTAS    => 'Plan de Cuotas',
            self::TARJETA        => 'Tarjeta',
        };
    }

    public function requiresVehicle(): bool
    {
        return $this === self::VEHICULO_CANJE;
    }

    public function requiresInstallmentPlan(): bool
    {
        return $this === self::PLAN_CUOTAS;
    }
}

enum InstallmentPlan: string
{
    case FRANCESA = 'FRANCESA';
    case ALEMANA  = 'ALEMANA';
    case MANUAL   = 'MANUAL';

    public function description(): string
    {
        return match ($this) {
            self::FRANCESA => 'Cuota fija (capital + interés constante)',
            self::ALEMANA  => 'Capital fijo + interés decreciente',
            self::MANUAL   => 'Montos definidos manualmente',
        };
    }
}
