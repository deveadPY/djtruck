<?php

declare(strict_types=1);

namespace App\Domain\Parts\Events;

class LowStockAlert
{
    public function __construct(
        public readonly int    $partId,
        public readonly string $codigo,
        public readonly string $descripcion,
        public readonly float  $stockActual,
        public readonly float  $stockMinimo,
    ) {}
}
