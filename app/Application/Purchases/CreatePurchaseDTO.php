<?php

declare(strict_types=1);

namespace App\Application\Purchases;

use Illuminate\Http\Request;

final readonly class CreatePurchaseDTO
{
    public function __construct(
        public int     $proveedorId,
        public string  $numeroFactura,
        public string  $fechaCompra,
        public string  $monedaCompra,
        public float   $tasaCambio,
        public ?string $observaciones,
        public array   $items,
        public array   $adjuntos,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            proveedorId:    (int) $request->input('proveedor_id'),
            numeroFactura:  $request->input('numero_factura', ''),
            fechaCompra:    $request->input('fecha_compra'),
            monedaCompra:   $request->input('moneda_compra', 'USD'),
            tasaCambio:     (float) ($request->input('tasa_cambio') ?? 1),
            observaciones:  $request->input('observaciones'),
            items:          $request->input('items') ?? [],
            adjuntos:       $request->file('adjuntos') ?? [],
        );
    }
}
