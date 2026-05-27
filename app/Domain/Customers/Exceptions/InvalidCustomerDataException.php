<?php

declare(strict_types=1);

namespace App\Domain\Customers\Exceptions;

use RuntimeException;

class InvalidCustomerDataException extends RuntimeException
{
    public static function missingRazonSocial(): self
    {
        return new self('La razón social del cliente es obligatoria.');
    }

    public static function negativeCreditLimit(float $value): self
    {
        return new self("La línea de crédito no puede ser negativa (recibido: {$value}).");
    }

    public static function invalidEmail(string $email): self
    {
        return new self("Email inválido: {$email}");
    }

    public static function inactive(string $ruc): self
    {
        return new self("El cliente {$ruc} está inactivo y no puede realizar nuevas ventas.");
    }
}
