<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exceptions;

use App\Domain\Shared\Exceptions\VehicleNotFoundException;

class InvalidVehicleStateException extends VehicleNotFoundException
{
    public static function notAvailable(int $vehicleId, string $estado, string $marca = '', string $modelo = ''): self
    {
        $descripcion = trim("{$marca} {$modelo}") ?: "ID {$vehicleId}";
        return new self(
            "El vehículo {$descripcion} no está disponible para la venta (estado actual: {$estado})."
        );
    }

    public static function notFound(int $vehicleId): self
    {
        return new self("El vehículo con ID {$vehicleId} no existe.");
    }
}
