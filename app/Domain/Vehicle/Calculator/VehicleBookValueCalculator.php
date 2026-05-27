<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Calculator;

use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * Calcula el valor en libros de un vehículo y sus métricas asociadas.
 *
 * Valor en libros = Costo de origen + Gastos acumulados
 * Precio sugerido = Valor en libros × (1 + margen)
 */
class VehicleBookValueCalculator
{
    private const DEFAULT_MARGIN_PCT = 25.0;

    public function calculate(float $costoOrigenUsd, float $totalGastosUsd): Money
    {
        $bookValue = $costoOrigenUsd + $totalGastosUsd;
        return new Money($bookValue, Currency::USD);
    }

    public function calculateForVehicle(int $vehicleId): Money
    {
        $vehicle = DB::table('vehiculos')->where('id', $vehicleId)->first();

        if (!$vehicle) {
            return Money::zero(Currency::USD);
        }

        return $this->calculate(
            (float) ($vehicle->costo_origen_usd ?? 0),
            (float) ($vehicle->total_gastos_usd ?? 0)
        );
    }

    public function calculateTotalExpenses(int $vehicleId): Money
    {
        $total = DB::table('vehiculo_gastos')
            ->where('vehiculo_id', $vehicleId)
            ->whereNull('deleted_at')
            ->sum('monto_usd');

        return new Money((float) $total, Currency::USD);
    }

    public function suggestedSalePrice(Money $bookValue, ?float $marginPct = null): Money
    {
        $margin = $marginPct ?? self::DEFAULT_MARGIN_PCT;
        $factor = 1 + ($margin / 100);
        return $bookValue->multiply($factor);
    }

    public function profitMargin(Money $salePrice, Money $bookValue): Money
    {
        return $salePrice->subtract($bookValue);
    }

    public function profitMarginPercentage(float $salePriceUsd, float $bookValueUsd): float
    {
        if ($bookValueUsd <= 0) {
            return 0.0;
        }
        $margin = $salePriceUsd - $bookValueUsd;
        return round(($margin / $bookValueUsd) * 100, 4);
    }
}
