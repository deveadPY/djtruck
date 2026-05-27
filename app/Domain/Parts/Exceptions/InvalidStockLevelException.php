<?php

declare(strict_types=1);

namespace App\Domain\Parts\Exceptions;

use RuntimeException;

class InvalidStockLevelException extends RuntimeException
{
    public static function negativeStock(float $value): self
    {
        return new self("El stock actual no puede ser negativo (recibido: {$value}).");
    }

    public static function invalidCommitment(float $comprometido, float $actual): self
    {
        return new self("Stock comprometido inválido ({$comprometido}). Debe estar entre 0 y stock actual ({$actual}).");
    }

    public static function negativeMinimum(float $value): self
    {
        return new self("El stock mínimo no puede ser negativo (recibido: {$value}).");
    }
}
