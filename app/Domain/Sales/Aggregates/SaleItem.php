<?php

declare(strict_types=1);

namespace App\Domain\Sales\Aggregates;

use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;

final readonly class SaleItem
{
    public function __construct(
        public int $itemableId,
        public string $itemableType,
        public string $descripcion,
        public float $cantidad,
        public Money $precioUnitario,
        public Money $costoSnapshot,
    ) {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }
        if (!$precioUnitario->isPositive() && !$precioUnitario->isZero()) {
            throw new InvalidArgumentException('El precio unitario no puede ser negativo.');
        }
    }

    public function subtotal(): Money
    {
        return $this->precioUnitario->multiply($this->cantidad);
    }

    public function costoTotal(): Money
    {
        return $this->costoSnapshot->multiply($this->cantidad);
    }

    public function isVehicle(): bool
    {
        return str_contains($this->itemableType, 'Vehicle');
    }

    public function isRepuesto(): bool
    {
        return str_contains($this->itemableType, 'Repuesto');
    }
}
