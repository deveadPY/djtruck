<?php

declare(strict_types=1);

namespace App\Domain\Sales\Validators;

use App\Domain\Sales\Exceptions\InvalidVehicleStateException;
use Illuminate\Support\Facades\DB;

/**
 * Garantiza que un vehículo puede venderse en su estado actual.
 *
 * Estados válidos para venta: DISPONIBLE, EN_PREPARACION, RESERVADO.
 * Estados bloqueados:         VENDIDO, BAJA, EN_TRANSITO, TOMA (canje recibido).
 */
final class VehicleStateValidator
{
    private const ESTADOS_VENDIBLES = ['DISPONIBLE', 'EN_PREPARACION', 'RESERVADO'];

    public function validate(int $vehicleId): void
    {
        $vehiculo = DB::table('vehiculos')
            ->where('id', $vehicleId)
            ->whereNull('deleted_at')
            ->first();

        if (!$vehiculo) {
            throw InvalidVehicleStateException::notFound($vehicleId);
        }

        if (!in_array($vehiculo->estado, self::ESTADOS_VENDIBLES, true)) {
            throw InvalidVehicleStateException::notAvailable(
                $vehicleId,
                $vehiculo->estado,
                $vehiculo->marca ?? '',
                $vehiculo->modelo ?? ''
            );
        }
    }

    /**
     * Valida todos los items de una venta, filtrando solo los de tipo Vehicle.
     */
    public function validateItems(array $items): void
    {
        foreach ($items as $item) {
            $itemableType = $item['itemable_type'] ?? '';
            if (!str_contains($itemableType, 'Vehicle')) {
                continue;
            }

            $vehicleId = (int) ($item['itemable_id'] ?? 0);
            if ($vehicleId > 0) {
                $this->validate($vehicleId);
            }
        }
    }
}
