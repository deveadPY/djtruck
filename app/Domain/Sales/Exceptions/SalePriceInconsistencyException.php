<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exceptions;

use App\Domain\Shared\Exceptions\SaleAmountMismatchException;

class SalePriceInconsistencyException extends SaleAmountMismatchException
{
    public static function noItems(): self
    {
        return new self('La venta debe contener al menos un item.');
    }

    public static function priceMismatch(float $expected, float $calculated): self
    {
        return new self(sprintf(
            'Inconsistencia detectada: el precio total de la venta (%.2f) no coincide con la suma de sus items (%.2f).',
            $expected,
            $calculated
        ));
    }
}
