<?php

declare(strict_types=1);

namespace App\Domain\Sales\ValueObjects;

final readonly class ImputationBreakdown
{
    public function __construct(
        public float $capital,
        public float $interes,
        public float $mora,
        public float $descuentoAnticipo,
        public float $total,
    ) {}

    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0);
    }

    public function toArray(): array
    {
        return [
            'capital'           => round($this->capital, 4),
            'interes'           => round($this->interes, 4),
            'mora'              => round($this->mora, 4),
            'descuento_anticipo' => round($this->descuentoAnticipo, 4),
            'total'             => round($this->total, 4),
        ];
    }
}
