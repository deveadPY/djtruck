<?php

declare(strict_types=1);

namespace App\Domain\Parts\Services;

/**
 * Calcula el costo promedio ponderado de stock al recibir nuevas unidades.
 * Fórmula:
 *   nuevoCosto = (stockActual * costoActual + cantidad * costoIngreso) / (stockActual + cantidad)
 */
final class PriceCalculatorService
{
    public function calcularCostoPromedio(
        float $stockActual,
        float $costoActualUsd,
        float $cantidadIngreso,
        float $costoUnitarioIngresoUsd
    ): float {
        $totalNuevo = $stockActual + $cantidadIngreso;
        if ($totalNuevo <= 0) {
            return 0;
        }

        $valorActual = $stockActual * $costoActualUsd;
        $valorIngreso = $cantidadIngreso * $costoUnitarioIngresoUsd;

        return round(($valorActual + $valorIngreso) / $totalNuevo, 4);
    }

    /**
     * Sugiere precio venta con margen objetivo (%).
     */
    public function sugerirPrecioVenta(float $costoPromedioUsd, float $margenObjetivoPct): float
    {
        if ($costoPromedioUsd <= 0 || $margenObjetivoPct < 0) {
            return 0;
        }
        return round($costoPromedioUsd * (1 + $margenObjetivoPct / 100), 4);
    }

    /**
     * Calcula margen % entre costo y precio.
     */
    public function margenPct(float $costoUsd, float $precioUsd): float
    {
        if ($costoUsd <= 0) {
            return 0;
        }
        return round((($precioUsd - $costoUsd) / $costoUsd) * 100, 2);
    }
}
