<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Processors;

use App\Domain\Purchases\Calculator\PurchaseCalculator;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Support\Facades\DB;

/**
 * Registra items de compra y actualiza el stock + costo promedio del repuesto.
 */
class PurchaseItemProcessor
{
    public function __construct(
        private readonly PurchaseCalculator $calculator
    ) {}

    public function process(int $compraId, array $items, Currency $moneda, float $tasaCambio): void
    {
        foreach ($items as $item) {
            $precioCompraUsd = $this->calculator->calculateUnitPriceUsd(
                (float) $item['precio_compra'],
                $moneda,
                $tasaCambio
            );

            $subtotalUsd = $this->calculator->calculateItemSubtotal(
                (float) $item['cantidad'],
                $precioCompraUsd
            );

            $this->insertPurchaseItem($compraId, $item, $precioCompraUsd, $subtotalUsd);
            $this->updateStockAndPrices($item, $precioCompraUsd);
        }
    }

    private function insertPurchaseItem(int $compraId, array $item, float $precioUsd, float $subtotalUsd): void
    {
        DB::table('compra_items')->insert([
            'compra_id'                 => $compraId,
            'repuesto_id'               => $item['repuesto_id'],
            'cantidad'                  => $item['cantidad'],
            'precio_compra_moneda'      => $item['precio_compra'],
            'precio_compra_usd'         => $precioUsd,
            'precio_venta_sugerido_usd' => $item['precio_venta_sugerido'] ?? null,
            'subtotal_usd'              => $subtotalUsd,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
    }

    private function updateStockAndPrices(array $item, float $precioCompraUsd): void
    {
        $producto = DB::table('stock_repuestos')->where('id', $item['repuesto_id'])->first();
        if (!$producto) {
            return;
        }

        $updateData = [
            'stock_actual'       => $producto->stock_actual + (int) $item['cantidad'],
            'costo_promedio_usd' => $precioCompraUsd,
            'updated_at'         => now(),
        ];

        if (!empty($item['precio_venta_sugerido'])) {
            $updateData['precio_venta_usd'] = $item['precio_venta_sugerido'];
        }

        DB::table('stock_repuestos')->where('id', $item['repuesto_id'])->update($updateData);
    }
}
