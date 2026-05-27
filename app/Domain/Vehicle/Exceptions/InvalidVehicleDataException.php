<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Exceptions;

use RuntimeException;

class InvalidVehicleDataException extends RuntimeException
{
    public static function invalidChassis(string $chassis): self
    {
        return new self("Número de chasis inválido: '{$chassis}'.");
    }

    public static function negativeCost(float $cost): self
    {
        return new self("El costo de origen no puede ser negativo (recibido: USD {$cost}).");
    }

    public static function invalidYear(int $year): self
    {
        $current = (int) date('Y');
        return new self("Año inválido: {$year}. Debe estar entre 1950 y " . ($current + 1) . '.');
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Campo requerido faltante: '{$field}'.");
    }
}
