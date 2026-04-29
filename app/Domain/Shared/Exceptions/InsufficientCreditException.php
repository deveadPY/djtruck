<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InsufficientCreditException extends \DomainException
{
    public static function forClient(
        int   $clienteId,
        float $requerido,
        float $disponible,
    ): self {
        return new self(sprintf(
            'El capital a financiar (USD %.2f) supera la línea de crédito disponible del cliente (USD %.2f).',
            $requerido,
            $disponible,
        ));
    }
}
