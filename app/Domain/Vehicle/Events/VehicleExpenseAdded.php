<?php

namespace App\Domain\Vehicle\Events;

class VehicleExpenseAdded
{
    public function __construct(
        public readonly int   $vehicleId,
        public readonly int   $expenseId,
        public readonly float $montoUsd,
        public readonly float $newBookValue,
    ) {}
}
