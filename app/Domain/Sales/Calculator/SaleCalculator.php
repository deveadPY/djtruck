<?php

declare(strict_types=1);

namespace App\Domain\Sales\Calculator;

use Illuminate\Support\Facades\DB;

class SaleCalculator
{
    public function calculateFinalPrice(float $precioVentaUsd, float $descuentoUsd): float
    {
        return max(0, $precioVentaUsd - $descuentoUsd);
    }

    public function calculateBookValue(array $items): float
    {
        $valorLibroTotal = 0.0;
        foreach ($items as $item) {
            $valorLibroTotal += (float) ($item['costo_snapshot_usd'] ?? 0) * (float) $item['cantidad'];
        }
        return $valorLibroTotal;
    }

    public function calculateGrossMargin(float $precioNeto, float $valorLibroTotal): float
    {
        return round($precioNeto - $valorLibroTotal, 4);
    }

    public function calculateMarginPercentage(float $margenBrutoUsd, float $valorLibroTotal): float
    {
        if ($valorLibroTotal <= 0) {
            return 0.0;
        }
        return round(($margenBrutoUsd / $valorLibroTotal) * 100, 4);
    }

    public function generateSaleNumber(): string
    {
        $count = DB::table('ventas')->count() + 1;
        return 'V-' . date('Ym') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function calculateInitialPaymentTotal(array $pagos): float
    {
        return array_sum(array_map(fn($p) => (float) ($p['monto_usd'] ?? 0), $pagos));
    }
}
