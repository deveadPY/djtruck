<?php

namespace App\Domain\Vehicle\Events;

class VehicleRegistered
{
    public function __construct(
        public readonly int    $vehicleId,
        public readonly string $chasis,
        public readonly float  $costOriginUsd,
    ) {}
}
