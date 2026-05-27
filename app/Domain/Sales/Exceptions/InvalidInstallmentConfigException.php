<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exceptions;

use App\Domain\Shared\Exceptions\InvalidInstallmentPlanException;

class InvalidInstallmentConfigException extends InvalidInstallmentPlanException
{
    public static function invalidType(string $tipoPlan): self
    {
        return new self("Tipo de plan inválido: '{$tipoPlan}'. Valores permitidos: FRANCESA, ALEMANA, MANUAL.");
    }

    public static function invalidCount(int $count, int $min, int $max): self
    {
        return new self("El número de cuotas debe estar entre {$min} y {$max} (recibido: {$count}).");
    }

    public static function manualRequiresInstallments(): self
    {
        return new self('Plan MANUAL requiere al menos una cuota definida.');
    }

    public static function invalidInstallmentAmount(int $index): self
    {
        return new self("Cuota #{$index} tiene monto inválido. Debe ser mayor a cero.");
    }

    public static function invalidFirstDate(string $fechaPrimeraCuota, string $fechaVenta): self
    {
        return new self(
            "La fecha de primera cuota ({$fechaPrimeraCuota}) no puede ser anterior a la fecha de venta ({$fechaVenta})."
        );
    }
}
