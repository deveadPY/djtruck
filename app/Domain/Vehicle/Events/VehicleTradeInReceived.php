<?php

namespace App\Domain\Vehicle\Events;

class VehicleTradeInReceived
{
    public function __construct(
        public readonly int   $vehicleId,
        public readonly int   $saleOriginId,
        public readonly float $valorTomaUsd,
    ) {}
}
