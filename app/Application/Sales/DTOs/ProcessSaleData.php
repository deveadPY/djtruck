<?php

declare(strict_types=1);

namespace App\Application\Sales\DTOs;

use App\Infrastructure\Http\Requests\StoreSaleRequest;

final readonly class ProcessSaleData
{
    /**
     * @param SaleItemData[]    $items
     * @param SalePaymentData[] $pagos
     * @param array             $cuotasManual  Filas del grid manual [{monto, fecha}]
     */
    public function __construct(
        // ── Campos de la venta ──────────────────────────────────────────
        public ?int    $vehiculoId,
        public int     $clienteId,
        public string  $fechaVenta,
        public string  $monedaVenta,
        public float   $precioVentaMoneda,
        public float   $precioVentaUsd,
        public float   $tasaCambioVenta,
        public string  $estado,
        public ?string $observaciones,
        public string  $modalidadPago,
        public float   $descuentoMoneda,
        public float   $descuentoUsd,

        // ── Carrito ──────────────────────────────────────────────────────
        public array   $items,

        // ── Pagos iniciales ──────────────────────────────────────────────
        public array   $pagos,

        // ── Plan de cuotas (solo cuando modalidadPago === 'CUOTAS') ──────
        public string  $tipoPlan,
        public float   $capitalTotalUsd,
        public int     $numeroCuotas,
        public float   $tasaInteresMensual,
        public string  $fechaPrimeraCuota,
        public int     $refuerzoCada,
        public float   $refuerzoMonto,
        public array   $cuotasManual,

        // ── Contexto de autenticación ────────────────────────────────────
        public int     $vendedorId,
    ) {}

    public static function fromRequest(StoreSaleRequest $request): self
    {
        $validated = $request->validated();

        $items = array_map(
            fn(array $item) => SaleItemData::fromArray($item),
            $request->input('items', [])
        );

        $pagos = array_filter(
            array_map(
                fn(array $pago) => SalePaymentData::fromArray($pago),
                $request->input('pagos', [])
            ),
            fn(SalePaymentData $p) => $p->montoUsd > 0
        );

        return new self(
            vehiculoId:          isset($validated['vehiculo_id']) ? (int) $validated['vehiculo_id'] : null,
            clienteId:           (int)   $validated['cliente_id'],
            fechaVenta:          $validated['fecha_venta'],
            monedaVenta:         $validated['moneda_venta'],
            precioVentaMoneda:   (float) $validated['precio_venta_moneda'],
            precioVentaUsd:      (float) $validated['precio_venta_usd'],
            tasaCambioVenta:     (float) ($validated['tasa_cambio_venta'] ?? 1),
            estado:              $validated['estado'],
            observaciones:       $validated['observaciones'] ?? null,
            modalidadPago:       $validated['modalidad_pago'],
            descuentoMoneda:     (float) ($validated['descuento_moneda'] ?? 0),
            descuentoUsd:        (float) ($validated['descuento_usd'] ?? 0),
            items:               array_values($items),
            pagos:               array_values($pagos),
            tipoPlan:            $request->input('tipo_plan', 'MANUAL'),
            capitalTotalUsd:     (float) $request->input('capital_total_usd', 0),
            numeroCuotas:        (int)   $request->input('numero_cuotas', 12),
            tasaInteresMensual:  (float) $request->input('tasa_interes_mensual', 0),
            fechaPrimeraCuota:   $request->input('fecha_primera_cuota', now()->addMonth()->toDateString()),
            refuerzoCada:        (int)   $request->input('refuerzo_cada', 0),
            refuerzoMonto:       (float) $request->input('refuerzo_monto', 0),
            cuotasManual:        $request->input('cuotas_manual', []),
            vendedorId:          (int)   $request->user()->id,
        );
    }
}
