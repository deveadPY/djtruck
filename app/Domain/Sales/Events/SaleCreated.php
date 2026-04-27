<?php

namespace App\Domain\Sales\Events;

class SaleCreated
{
    public function __construct(
        public readonly int $saleId,
        public readonly int $vehicleId,
    ) {}
}
