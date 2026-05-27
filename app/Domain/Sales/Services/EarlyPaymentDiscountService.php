<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use Carbon\Carbon;

/**
 * Calcula descuento por pronto pago (anticipo de cuotas).
 *
 * El descuento se aplica sobre el CAPITAL de las cuotas que se pagan
 * antes de su fecha de vencimiento. La lógica es:
 *
 *   - Si la cuota se paga antes de su vencimiento, aplica descuento
 *     sobre el capital proporcional a los días de anticipación.
 *   - El porcentaje de descuento es configurable y SIEMPRE opcional:
 *     el usuario de cobro decide si aplicarlo o no.
 *
 * Fórmula simple:
 *   descuento = capital * (pct_descuento / 100)
 *
 * Para un modelo más sofisticado (proporcional a días):
 *   descuento = capital * (pct_descuento / 100) * (dias_anticipo / 30)
 */
final class EarlyPaymentDiscountService
{
    /**
     * Calcula el descuento por anticipo para UNA cuota.
     *
     * @param float  $capital         Capital de la cuota
     * @param string $fechaVencimiento Fecha de vencimiento (Y-m-d)
     * @param string $fechaPago        Fecha efectiva de pago (Y-m-d)
     * @param float  $porcentajeDescuento Porcentaje de descuento (ej. 5.0 = 5%)
     * @param bool   $proporcional     Si true, escala por días de anticipación
     *
     * @return float Monto del descuento
     */
    public function calcular(
        float  $capital,
        string $fechaVencimiento,
        string $fechaPago,
        float  $porcentajeDescuento,
        bool   $proporcional = false,
    ): float {
        if ($porcentajeDescuento <= 0 || $capital <= 0) {
            return 0;
        }

        $vencimiento = Carbon::parse($fechaVencimiento);
        $pago        = Carbon::parse($fechaPago);

        // Solo aplica si se paga antes del vencimiento
        if (!$pago->lt($vencimiento)) {
            return 0;
        }

        $diasAnticipo = $vencimiento->diffInDays($pago);

        if ($proporcional && $diasAnticipo > 0) {
            // Escala lineal: a más días de anticipación, más descuento
            // Máximo: 30 días de anticipación = 100% del porcentaje
            $factor = min(1.0, $diasAnticipo / 30.0);
            return round($capital * ($porcentajeDescuento / 100) * $factor, 4);
        }

        // Descuento fijo: se aplica el porcentaje completo
        return round($capital * ($porcentajeDescuento / 100), 4);
    }

    /**
     * Calcula el descuento total para múltiples cuotas.
     *
     * @param array $cuotas Cada elemento: ['capital' => float, 'fecha_vencimiento' => string]
     */
    public function calcularMultiple(
        array $cuotas,
        string $fechaPago,
        float  $porcentajeDescuento,
        bool   $proporcional = false,
    ): float {
        $totalDescuento = 0;
        foreach ($cuotas as $cuota) {
            $totalDescuento += $this->calcular(
                (float) $cuota['capital'],
                $cuota['fecha_vencimiento'],
                $fechaPago,
                $porcentajeDescuento,
                $proporcional,
            );
        }
        return round($totalDescuento, 4);
    }
}
