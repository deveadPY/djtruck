<?php

declare(strict_types=1);

namespace App\Domain\Sales\Validators;

use App\Domain\Sales\Exceptions\DuplicateVehicleSaleException;
use Illuminate\Support\Facades\DB;

/**
 * Bloquea la creación de una venta cuando el vehículo ya tiene una venta
 * activa (no cancelada, no eliminada).
 */
final class DuplicateVehicleSaleValidator
{
    private const ESTADOS_VENTA_ACTIVA = ['EN_PROCESO', 'COMPLETADO', 'COMPLETADA'];

    public function validate(int $vehicleId, ?int $excludeSaleId = null): void
    {
        $query = DB::table('venta_items')
            ->join('ventas', 'venta_items.venta_id', '=', 'ventas.id')
            ->where('venta_items.itemable_id', $vehicleId)
            ->where('venta_items.itemable_type', 'like', '%Vehicle%')
            ->whereIn('ventas.estado', self::ESTADOS_VENTA_ACTIVA)
            ->whereNull('venta_items.deleted_at')
            ->whereNull('ventas.deleted_at');

        if ($excludeSaleId !== null) {
            $query->where('ventas.id', '!=', $excludeSaleId);
        }

        if ($query->exists()) {
            throw new DuplicateVehicleSaleException($vehicleId);
        }
    }

    /**
     * Valida todos los items de una venta, filtrando solo los de tipo Vehicle.
     */
    public function validateItems(array $items, ?int $excludeSaleId = null): void
    {
        foreach ($items as $item) {
            $itemableType = $item['itemable_type'] ?? '';
            if (!str_contains($itemableType, 'Vehicle')) {
                continue;
            }

            $vehicleId = (int) ($item['itemable_id'] ?? 0);
            if ($vehicleId > 0) {
                $this->validate($vehicleId, $excludeSaleId);
            }
        }
    }
}
