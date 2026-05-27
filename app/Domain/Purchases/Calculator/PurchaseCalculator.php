<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Calculator;

use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;

/**
 * Calcula totales, conversiones de moneda y subtotales para una compra.
 */
class PurchaseCalculator
{
    public function calculateTotalInMoney(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['cantidad'] * (float) $item['precio_compra'];
        }
        return $total;
    }

    public function convertToUsd(float $amount, Currency $moneda, float $tasaCambio): float
    {
        if ($moneda === Currency::USD) {
            return $amount;
        }

        if ($tasaCambio <= 0) {
            return 0.0;
        }

        return round($amount / $tasaCambio, 2);
    }

    public function calculateItemSubtotal(float $cantidad, float $precioCompraUsd): float
    {
        return round($cantidad * $precioCompraUsd, 2);
    }

    public function calculateUnitPriceUsd(float $precioCompra, Currency $moneda, float $tasaCambio): float
    {
        if ($moneda === Currency::USD) {
            return round($precioCompra, 2);
        }
        return round($precioCompra / $tasaCambio, 2);
    }
}
