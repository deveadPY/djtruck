<?php

declare(strict_types=1);

namespace App\Domain\Quotes\Exceptions;

use App\Domain\Quotes\ValueObjects\QuoteStatus;
use RuntimeException;

class QuoteException extends RuntimeException
{
    public static function invalidTransition(QuoteStatus $actual, QuoteStatus $nuevo): self
    {
        return new self(
            "Transición no permitida: presupuesto está en '{$actual->value}' y no puede pasar a '{$nuevo->value}'."
        );
    }

    public static function expired(string $numero, string $vigencia): self
    {
        return new self("El presupuesto {$numero} venció el {$vigencia} y no puede convertirse a venta.");
    }

    public static function noItems(): self
    {
        return new self('Un presupuesto debe tener al menos un item.');
    }

    public static function alreadyConverted(string $numero): self
    {
        return new self("El presupuesto {$numero} ya fue convertido a venta.");
    }
}
