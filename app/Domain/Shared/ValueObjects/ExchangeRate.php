<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

final readonly class ExchangeRate
{
    public function __construct(
        public float    $value,
        public Currency $from,
        public Currency $to,
        public string   $source,
        public Carbon   $timestamp,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException("ExchangeRate debe ser positivo, recibido: {$value}");
        }
    }

    public static function parity(Currency $currency): self
    {
        return new self(1.0, $currency, $currency, 'parity', Carbon::now());
    }

    public function invert(): self
    {
        return new self(1 / $this->value, $this->to, $this->from,
            $this->source . '_inverted', $this->timestamp);
    }

    public function toArray(): array
    {
        return [
            'from'      => $this->from->value,
            'to'        => $this->to->value,
            'rate'      => $this->value,
            'source'    => $this->source,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
