<?php

declare(strict_types=1);

namespace App\Domain\Quotes\Events;

class QuoteConvertedToSale
{
    public function __construct(
        public readonly int $quoteId,
        public readonly int $ventaId,
    ) {}
}
