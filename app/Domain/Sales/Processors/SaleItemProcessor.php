<?php

declare(strict_types=1);

namespace App\Domain\Sales\Processors;

use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Domain\Sales\Services\ItemDescriptionResolver;
use Illuminate\Support\Facades\DB;

class SaleItemProcessor
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly ItemDescriptionResolver $descriptionResolver
    ) {}

    public function process(int $ventaId, array $items, float $tasaConversion): void
    {
        $itemRows = [];

        foreach ($items as $item) {
            $itemRows[] = $this->buildItemRow($item, $tasaConversion);
            $this->updateInventoryState($item);
        }

        if (!empty($itemRows)) {
            $this->saleRepository->addItems($ventaId, $itemRows);
        }
    }

    private function buildItemRow(array $item, float $tasaConversion): array
    {
        $precioMoneda = (float) $item['precio_unitario_usd'] * $tasaConversion;
        $subtotalMoneda = $precioMoneda * (float) $item['cantidad'];

        return [
            'itemable_id' => $item['itemable_id'],
            'itemable_type' => $item['itemable_type'],
            'descripcion' => $this->descriptionResolver->resolve($item),
            'cantidad' => $item['cantidad'],
            'precio_unitario_moneda' => $precioMoneda,
            'precio_unitario_usd' => $item['precio_unitario_usd'],
            'subtotal_moneda' => $subtotalMoneda,
            'subtotal_usd' => (float) $item['precio_unitario_usd'] * (float) $item['cantidad'],
            'costo_snapshot_usd' => $item['costo_snapshot_usd'] ?? 0,
        ];
    }

    private function updateInventoryState(array $item): void
    {
        $type = $item['itemable_type'];

        if ($this->isVehicleType($type)) {
            DB::table('vehiculos')->where('id', $item['itemable_id'])->update([
                'estado' => 'VENDIDO',
                'updated_at' => now(),
            ]);
        }

        if ($this->isRepuestoType($type)) {
            DB::table('stock_repuestos')
                ->where('id', $item['itemable_id'])
                ->decrement('stock_actual', (int) $item['cantidad']);
        }
    }

    public function revert(int $ventaId): void
    {
        $items = $this->saleRepository->getItems($ventaId);

        foreach ($items as $item) {
            $type = $item->itemable_type;

            if ($this->isVehicleType($type)) {
                DB::table('vehiculos')->where('id', $item->itemable_id)->update([
                    'estado' => 'DISPONIBLE',
                    'updated_at' => now(),
                ]);
            }

            if ($this->isRepuestoType($type)) {
                DB::table('stock_repuestos')
                    ->where('id', $item->itemable_id)
                    ->increment('stock_actual', (int) $item->cantidad);
            }
        }

        $this->saleRepository->removeItems($ventaId);
    }

    private function isVehicleType(string $type): bool
    {
        return $type === 'App\\Models\\Vehicle' || str_contains($type, 'VehicleModel');
    }

    private function isRepuestoType(string $type): bool
    {
        return $type === 'App\\Models\\StockRepuesto' || str_contains($type, 'RepuestoModel');
    }
}
