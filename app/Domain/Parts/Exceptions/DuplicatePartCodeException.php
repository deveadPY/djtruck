<?php

declare(strict_types=1);

namespace App\Domain\Parts\Exceptions;

use RuntimeException;

class DuplicatePartCodeException extends RuntimeException
{
    public static function forCode(string $code): self
    {
        return new self("Ya existe un repuesto con el código: {$code}");
    }
}
