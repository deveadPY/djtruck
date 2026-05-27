<?php

declare(strict_types=1);

namespace App\Domain\Quotes\Events;

class QuoteCreated
{
    public function __construct(
        public readonly int    $quoteId,
        public readonly string $numero,
        public readonly int    $clienteId,
        public readonly float  $totalUsd,
    ) {}
}
