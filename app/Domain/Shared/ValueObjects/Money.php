<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public float $amount;

    public function __construct(
        float           $amount,
        public Currency $currency,
        public ?ExchangeRate $rateUsed = null,
    ) {
        $this->amount = round($amount, $currency->decimals());
    }

    public static function zero(Currency $currency): self { return new self(0, $currency); }

    public static function of(float $amount, string $code): self
    {
        return new self($amount, Currency::from($code));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    public function divide(float $divisor): self
    {
        if ($divisor == 0) throw new InvalidArgumentException('No se puede dividir por cero.');
        return new self($this->amount / $divisor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->amount === $other->amount;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function isZero(): bool     { return $this->amount == 0; }
    public function isPositive(): bool { return $this->amount > 0; }
    public function isNegative(): bool { return $this->amount < 0; }

    public function format(): string
    {
        $formatted = match ($this->currency) {
            Currency::USD => number_format($this->amount, 2, '.', ','),
            Currency::PYG => number_format($this->amount, 0, ',', '.'),
            Currency::BRL => number_format($this->amount, 2, ',', '.'),
        };
        return $this->currency->symbol() . ' ' . $formatted;
    }

    public function toArray(): array
    {
        return [
            'amount'    => $this->amount,
            'currency'  => $this->currency->value,
            'symbol'    => $this->currency->symbol(),
            'formatted' => $this->format(),
            'rate_used' => $this->rateUsed?->toArray(),
        ];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "No se puede operar con monedas distintas: {$this->currency->value} vs {$other->currency->value}"
            );
        }
    }
}
