<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero_venta'   => $this->numero_venta,
            'fecha_venta'    => $this->fecha_venta,
            'estado'         => $this->estado,
            'modalidad_pago' => $this->modalidad_pago,
            'cliente'        => $this->whenLoaded('cliente', fn() => [
                'id'           => $this->cliente->id,
                'razon_social' => $this->cliente->razon_social,
                'ruc'          => $this->cliente->ruc,
                'email'        => $this->cliente->email,
            ]),
            'precio' => [
                'moneda'          => $this->moneda_venta,
                'precio_moneda'   => (float) $this->precio_venta_moneda,
                'precio_usd'      => (float) $this->precio_venta_usd,
                'descuento_usd'   => (float) ($this->descuento_usd ?? 0),
                'tasa_cambio'     => (float) ($this->tasa_cambio_venta ?? 1),
            ],
            'rentabilidad' => [
                'valor_libro_usd'  => (float) ($this->valor_libro_snapshot ?? 0),
                'margen_bruto_usd' => (float) ($this->margen_bruto_usd ?? 0),
                'margen_pct'       => (float) ($this->margen_pct ?? 0),
            ],
            'plan_cuotas'    => $this->whenLoaded('planCuotas', fn() =>
                $this->planCuotas ? [
                    'id'                    => $this->planCuotas->id,
                    'tipo_plan'             => $this->planCuotas->tipo_plan,
                    'numero_cuotas'         => $this->planCuotas->numero_cuotas,
                    'capital_total_usd'     => (float) $this->planCuotas->capital_total_usd,
                    'tasa_interes_mensual'  => (float) $this->planCuotas->tasa_interes_mensual,
                    'estado'                => $this->planCuotas->estado,
                ] : null
            ),
            'items'          => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'                  => $item->id,
                    'descripcion'         => $item->descripcion,
                    'cantidad'            => (float) $item->cantidad,
                    'precio_unitario_usd' => (float) $item->precio_unitario_usd,
                    'subtotal_usd'        => (float) $item->subtotal_usd,
                ])
            ),
            'observaciones'  => $this->observaciones,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
