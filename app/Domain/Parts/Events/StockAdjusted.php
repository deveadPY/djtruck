<?php

declare(strict_types=1);

namespace App\Domain\Parts\Events;

class StockAdjusted
{
    public function __construct(
        public readonly int    $partId,
        public readonly string $codigo,
        public readonly float  $stockAnterior,
        public readonly float  $stockNuevo,
        public readonly string $motivo,
        public readonly ?int   $userId,
    ) {}
}
