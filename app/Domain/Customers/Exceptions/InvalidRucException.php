<?php

declare(strict_types=1);

namespace App\Domain\Customers\Exceptions;

use RuntimeException;

class InvalidRucException extends RuntimeException
{
    public static function empty(): self
    {
        return new self('El RUC/CI no puede estar vacío.');
    }

    public static function invalidFormat(string $raw): self
    {
        return new self("El RUC/CI '{$raw}' tiene un formato inválido. Use solo dígitos, guiones o caracteres alfanuméricos (4-20 chars).");
    }
}
