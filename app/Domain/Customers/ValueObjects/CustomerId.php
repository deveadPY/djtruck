<?php

declare(strict_types=1);

namespace App\Domain\Customers\ValueObjects;

final class CustomerId
{
    private function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("CustomerId debe ser positivo, recibido: {$value}");
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(CustomerId $other): bool
    {
        return $this->value === $other->value;
    }
}
