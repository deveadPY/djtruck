<?php

declare(strict_types=1);

namespace App\Domain\Sales\Validators;

use App\Domain\Sales\Exceptions\InvalidVehicleStateException;
use App\Domain\Sales\Exceptions\SalePriceInconsistencyException;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SaleIntegrityValidator
{
    private const PRICE_TOLERANCE = 0.01;
    private const VALID_VEHICLE_STATES = ['DISPONIBLE', 'RESERVADO'];

    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository
    ) {}

    public function validate(array $items, float $expectedTotalUsd): void
    {
        $this->validateItemsNotEmpty($items);
        $totalCalculated = $this->validateAndSumItems($items);
        $this->validatePriceConsistency($totalCalculated, $expectedTotalUsd);
    }

    private function validateItemsNotEmpty(array $items): void
    {
        if (empty($items)) {
            throw SalePriceInconsistencyException::noItems();
        }
    }

    private function validateAndSumItems(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $type = $item['itemable_type'];
            $id = (int) $item['itemable_id'];
            $precio = (float) $item['precio_unitario_usd'];
            $cantidad = (float) $item['cantidad'];

            if ($this->isVehicleType($type)) {
                $this->validateVehicleAvailable($id);
            }

            if ($this->isRepuestoType($type)) {
                $this->validateRepuestoStock($id, $cantidad, $item['descripcion'] ?? (string) $id);
            }

            $total += $precio * $cantidad;
        }

        return $total;
    }

    private function validateVehicleAvailable(int $vehicleId): void
    {
        $vehicle = $this->vehicleRepository->findById($vehicleId);

        if (!$vehicle) {
            throw InvalidVehicleStateException::notFound($vehicleId);
        }

        if (!in_array($vehicle->estado, self::VALID_VEHICLE_STATES, true)) {
            throw InvalidVehicleStateException::notAvailable(
                $vehicleId,
                $vehicle->estado,
                $vehicle->marca ?? '',
                $vehicle->modelo ?? ''
            );
        }
    }

    private function validateRepuestoStock(int $repuestoId, float $cantidad, string $descripcion): void
    {
        $repuesto = DB::table('stock_repuestos')->where('id', $repuestoId)->first();

        if (!$repuesto || $repuesto->stock_actual < $cantidad) {
            throw new InsufficientStockException(
                "No hay stock suficiente para el repuesto: {$descripcion}"
            );
        }
    }

    private function validatePriceConsistency(float $calculated, float $expected): void
    {
        if (abs($calculated - $expected) > self::PRICE_TOLERANCE) {
            throw SalePriceInconsistencyException::priceMismatch($expected, $calculated);
        }
    }

    private function isVehicleType(string $type): bool
    {
        return str_contains($type, 'Vehicle');
    }

    private function isRepuestoType(string $type): bool
    {
        return str_contains($type, 'Repuesto');
    }
}
