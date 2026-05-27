<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $costoTotal  = (float) $this->costo_origen_usd + (float) ($this->total_gastos_usd ?? 0);
        $precioVenta = (float) ($this->precio_venta_sugerido_usd ?? 0);

        return [
            'id'                => $this->id,
            'numero_chasis'     => $this->numero_chasis,
            'numero_motor'      => $this->numero_motor,
            'marca'             => $this->marca,
            'modelo'            => $this->modelo,
            'anio'              => $this->anio,
            'color'             => $this->color,
            'tipo_vehiculo'     => $this->tipo_vehiculo,
            'kilometraje'       => $this->kilometraje,
            'estado'            => $this->estado,
            'ubicacion'         => $this->ubicacion,
            'costo' => [
                'moneda'              => $this->moneda_costo,
                'costo_origen_usd'    => (float) $this->costo_origen_usd,
                'total_gastos_usd'    => (float) ($this->total_gastos_usd ?? 0),
                'valor_libro_usd'     => round($costoTotal, 2),
                'precio_sugerido_usd' => $precioVenta ?: null,
                'margen_obj_pct'      => $this->margen_objetivo_pct ? (float) $this->margen_objetivo_pct : null,
            ],
            'proveedor_id'      => $this->proveedor_id,
            'proveedor'         => $this->whenLoaded('proveedor', fn() => [
                'id'           => $this->proveedor->id,
                'razon_social' => $this->proveedor->razon_social,
            ]),
            'imagenes'          => $this->whenLoaded('imagenes', fn() =>
                $this->imagenes->map(fn($img) => [
                    'id'        => $img->id,
                    'url'       => asset($img->ruta),
                    'es_portada'=> (bool) $img->es_portada,
                    'orden'     => $img->orden,
                ])
            ),
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}
