<?php

declare(strict_types=1);

namespace App\Domain\Parts\ValueObjects;

use App\Domain\Parts\Exceptions\InvalidStockLevelException;

/**
 * Representa el nivel de stock de un repuesto:
 *   - actual: cantidad física en almacén
 *   - comprometido: reservado (por presupuestos pendientes)
 *   - minimo: umbral de reorden
 *
 * disponible = actual - comprometido
 */
final class StockLevel
{
    private function __construct(
        public readonly float $actual,
        public readonly float $comprometido,
        public readonly float $minimo,
    ) {
        if ($actual < 0) {
            throw InvalidStockLevelException::negativeStock($actual);
        }
        if ($comprometido < 0 || $comprometido > $actual) {
            throw InvalidStockLevelException::invalidCommitment($comprometido, $actual);
        }
        if ($minimo < 0) {
            throw InvalidStockLevelException::negativeMinimum($minimo);
        }
    }

    public static function of(float $actual, float $comprometido = 0, float $minimo = 0): self
    {
        return new self($actual, $comprometido, $minimo);
    }

    public function disponible(): float
    {
        return max(0, $this->actual - $this->comprometido);
    }

    public function bajoMinimo(): bool
    {
        return $this->minimo > 0 && $this->disponible() <= $this->minimo;
    }

    public function alcanzaPara(float $cantidad): bool
    {
        return $this->disponible() >= $cantidad;
    }
}
