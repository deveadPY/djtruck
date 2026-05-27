<?php

declare(strict_types=1);

namespace App\Application\Sales;

use Illuminate\Http\Request;

/**
 * DTO para venta híbrida: combina efectivo, canje de vehículo y plan de cuotas.
 */
final readonly class ProcessHybridSaleDTO
{
    public function __construct(
        public int     $vehiculoId,
        public int     $clienteId,
        public ?int    $vendedorId,
        public string  $monedaVenta,
        public float   $precioVenta,
        public array   $pagos,
        public ?string $observaciones,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vehiculoId:    (int) $request->input('vehiculo_id'),
            clienteId:     (int) $request->input('cliente_id'),
            vendedorId:    $request->input('vendedor_id') ? (int) $request->input('vendedor_id') : null,
            monedaVenta:   (string) $request->input('moneda_venta'),
            precioVenta:   (float) $request->input('precio_venta'),
            pagos:         (array) $request->input('pagos', []),
            observaciones: $request->input('observaciones'),
        );
    }

    public function findPaymentByType(string $tipo): ?array
    {
        foreach ($this->pagos as $pago) {
            if (($pago['tipo'] ?? '') === $tipo) {
                return $pago;
            }
        }
        return null;
    }
}
