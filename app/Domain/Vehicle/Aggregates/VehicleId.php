<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Aggregates;

use InvalidArgumentException;

final readonly class VehicleId
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('VehicleId debe ser un entero positivo.');
        }
    }

    public static function from(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
