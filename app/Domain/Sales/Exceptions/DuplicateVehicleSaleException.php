<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exceptions;

use RuntimeException;

class DuplicateVehicleSaleException extends RuntimeException
{
    public readonly int $vehicleId;

    public function __construct(int $vehicleId)
    {
        $this->vehicleId = $vehicleId;
        parent::__construct(
            "El vehículo con ID {$vehicleId} ya tiene una venta activa registrada."
        );
    }
}
