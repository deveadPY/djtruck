<?php

declare(strict_types=1);

namespace App\Domain\Parts\Exceptions;

use RuntimeException;

class InvalidPartCodeException extends RuntimeException
{
    public static function empty(): self
    {
        return new self('El código del repuesto no puede estar vacío.');
    }

    public static function invalidFormat(string $raw): self
    {
        return new self("El código '{$raw}' tiene formato inválido. Use 2-50 caracteres alfanuméricos, guiones, puntos o slash.");
    }
}
