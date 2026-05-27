<?php

declare(strict_types=1);

namespace App\Domain\Parts\Events;

class PartCreated
{
    public function __construct(
        public readonly int $partId,
        public readonly string $codigo,
        public readonly float $stockInicial,
    ) {}
}
