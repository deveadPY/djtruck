<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Infrastructure\Http\Requests\StoreSaleRequest;

final readonly class CreateSaleDTO
{
    public function __construct(
        public int     $clienteId,
        public string  $fechaVenta,
        public string  $monedaVenta,
        public float   $precioVentaMoneda,
        public float   $precioVentaUsd,
        public string  $modalidadPago,
        public string  $estado,
        public float   $tasaCambioVenta,
        public float   $descuentoMoneda,
        public float   $descuentoUsd,
        public ?string $observaciones,
        public array   $items,
        public array   $pagos,
        // Vehículo principal (opcional, se resuelve desde items si no se especifica)
        public ?int    $vehiculoId,
        // Plan de cuotas (solo para modalidad CUOTAS)
        public ?string $tipoPlan,
        public ?float  $capitalTotalUsd,
        public ?int    $numeroCuotas,
        public ?float  $tasaInteresMensual,
        public ?string $fechaPrimeraCuota,
        public array   $cuotasManual,
        public ?int    $refuerzoCada,
        public ?float  $refuerzoMonto,
    ) {}

    public static function fromRequest(StoreSaleRequest $request): self
    {
        $vehiculoIdInput = $request->validated('vehiculo_id') ?? $request->input('vehiculo_id');

        return new self(
            clienteId:          (int) $request->validated('cliente_id'),
            fechaVenta:         $request->validated('fecha_venta'),
            monedaVenta:        $request->validated('moneda_venta'),
            precioVentaMoneda:  (float) $request->validated('precio_venta_moneda'),
            precioVentaUsd:     (float) $request->validated('precio_venta_usd'),
            modalidadPago:      $request->validated('modalidad_pago'),
            estado:             $request->validated('estado'),
            tasaCambioVenta:    (float) ($request->validated('tasa_cambio_venta') ?? 1),
            descuentoMoneda:    (float) ($request->validated('descuento_moneda') ?? 0),
            descuentoUsd:       (float) ($request->validated('descuento_usd') ?? 0),
            observaciones:      $request->validated('observaciones'),
            items:              $request->validated('items') ?? [],
            pagos:              $request->input('pagos') ?? [],
            vehiculoId:         $vehiculoIdInput !== null && $vehiculoIdInput !== ''
                                    ? (int) $vehiculoIdInput
                                    : null,
            tipoPlan:           $request->validated('tipo_plan'),
            capitalTotalUsd:    $request->validated('capital_total_usd') !== null
                                    ? (float) $request->validated('capital_total_usd')
                                    : null,
            numeroCuotas:       $request->validated('numero_cuotas') !== null
                                    ? (int) $request->validated('numero_cuotas')
                                    : null,
            tasaInteresMensual: $request->validated('tasa_interes_mensual') !== null
                                    ? (float) $request->validated('tasa_interes_mensual')
                                    : null,
            fechaPrimeraCuota:  $request->validated('fecha_primera_cuota'),
            cuotasManual:       $request->input('cuotas_manual') ?? [],
            refuerzoCada:       $request->input('refuerzo_cada') ? (int) $request->input('refuerzo_cada') : null,
            refuerzoMonto:      $request->input('refuerzo_monto') ? (float) $request->input('refuerzo_monto') : null,
        );
    }

    /**
     * Resuelve el ID del vehículo principal.
     * Si no fue especificado explícitamente, busca el primer item que sea un vehículo.
     */
    public function resolveVehiculoPrincipal(): ?int
    {
        if ($this->vehiculoId !== null) {
            return $this->vehiculoId;
        }

        foreach ($this->items as $item) {
            if (str_contains($item['itemable_type'] ?? '', 'Vehicle')) {
                return (int) $item['itemable_id'];
            }
        }

        return null;
    }
}
