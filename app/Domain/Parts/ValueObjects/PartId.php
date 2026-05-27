<?php

declare(strict_types=1);

namespace App\Domain\Parts\ValueObjects;

final class PartId
{
    private function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("PartId debe ser positivo, recibido: {$value}");
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
}
