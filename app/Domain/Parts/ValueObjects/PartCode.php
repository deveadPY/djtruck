<?php

declare(strict_types=1);

namespace App\Domain\Parts\ValueObjects;

use App\Domain\Parts\Exceptions\InvalidPartCodeException;

/**
 * Código único de repuesto. Acepta alfanuméricos + guiones + puntos (4-50 chars).
 */
final class PartCode
{
    private function __construct(private readonly string $value) {}

    public static function parse(string $raw): self
    {
        $value = strtoupper(trim($raw));

        if ($value === '') {
            throw InvalidPartCodeException::empty();
        }

        if (!preg_match('/^[A-Z0-9\-\.\/_]{2,50}$/', $value)) {
            throw InvalidPartCodeException::invalidFormat($raw);
        }

        return new self($value);
    }

    public function value(): string { return $this->value; }
    public function equals(PartCode $other): bool { return $this->value === $other->value; }
}
