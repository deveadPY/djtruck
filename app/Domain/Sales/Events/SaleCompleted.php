<?php

namespace App\Domain\Sales\Events;

class SaleCompleted
{
    public function __construct(
        public readonly int   $saleId,
        public readonly int   $vehicleId,
        public readonly int   $clienteId,
        public readonly float $totalUsd,
    ) {}
}
