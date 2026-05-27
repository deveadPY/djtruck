<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve descripciones legibles para items de venta a partir de su tipo polimórfico.
 *
 * Si el cliente ya envió una descripción, la respeta. Si no, consulta el repositorio
 * o tabla correspondiente para construir una descripción enriquecida.
 */
class ItemDescriptionResolver
{
    private const FALLBACK_DESCRIPTION = 'Item sin descripción';

    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository
    ) {}

    public function resolve(array $item): string
    {
        $providedDescription = trim((string) ($item['descripcion'] ?? ''));
        if ($providedDescription !== '') {
            return $providedDescription;
        }

        $type = (string) ($item['itemable_type'] ?? '');
        $id = (int) ($item['itemable_id'] ?? 0);

        if ($id === 0) {
            return self::FALLBACK_DESCRIPTION;
        }

        return match (true) {
            $this->isVehicleType($type)   => $this->buildVehicleDescription($id),
            $this->isRepuestoType($type)  => $this->buildRepuestoDescription($id),
            default                       => self::FALLBACK_DESCRIPTION,
        };
    }

    private function buildVehicleDescription(int $vehicleId): string
    {
        $vehicle = $this->vehicleRepository->findById($vehicleId);

        if (!$vehicle) {
            return self::FALLBACK_DESCRIPTION;
        }

        $parts = array_filter([
            $vehicle->marca ?? null,
            $vehicle->modelo ?? null,
            $vehicle->anio ?? null,
        ]);

        $descripcion = implode(' ', $parts);

        if (!empty($vehicle->numero_chasis)) {
            $descripcion .= " (Chasis: {$vehicle->numero_chasis})";
        }

        return $descripcion !== '' ? $descripcion : self::FALLBACK_DESCRIPTION;
    }

    private function buildRepuestoDescription(int $repuestoId): string
    {
        $repuesto = DB::table('stock_repuestos')->where('id', $repuestoId)->first();

        if (!$repuesto) {
            return self::FALLBACK_DESCRIPTION;
        }

        $codigo = !empty($repuesto->codigo) ? "[{$repuesto->codigo}] " : '';
        $descripcion = $repuesto->descripcion ?? '';

        $resultado = trim($codigo . $descripcion);

        return $resultado !== '' ? $resultado : self::FALLBACK_DESCRIPTION;
    }

    private function isVehicleType(string $type): bool
    {
        return str_contains($type, 'Vehicle');
    }

    private function isRepuestoType(string $type): bool
    {
        return str_contains($type, 'Repuesto') || str_contains($type, 'StockRepuesto');
    }
}
