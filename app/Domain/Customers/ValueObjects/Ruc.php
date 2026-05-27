<?php

declare(strict_types=1);

namespace App\Domain\Customers\ValueObjects;

use App\Domain\Customers\Exceptions\InvalidRucException;

/**
 * RUC paraguayo: formato N{1,8}-N{1} o N{1,8} (sin DV).
 * Acepta también CI/Pasaporte para clientes extranjeros (8-20 chars alfanuméricos).
 */
final class Ruc
{
    private function __construct(private readonly string $value) {}

    public static function parse(string $raw): self
    {
        $value = strtoupper(trim(str_replace(['.', ' '], '', $raw)));

        if ($value === '') {
            throw InvalidRucException::empty();
        }

        if (!preg_match('/^[0-9A-Z\-]{4,20}$/', $value)) {
            throw InvalidRucException::invalidFormat($raw);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Ruc $other): bool
    {
        return $this->value === $other->value;
    }
}
