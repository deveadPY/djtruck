<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $montoPendiente = ((float) $this->capital + (float) $this->interes + (float) ($this->interes_mora ?? 0))
            - (float) ($this->monto_pagado ?? 0);

        return [
            'id'                => $this->id,
            'numero_cuota'      => $this->numero_cuota,
            'total_cuotas'      => $this->total_cuotas,
            'tipo_plan'         => $this->tipo_plan,
            'moneda'            => $this->moneda,
            'capital'           => (float) $this->capital,
            'interes'           => (float) $this->interes,
            'interes_mora'      => (float) ($this->interes_mora ?? 0),
            'monto_pagado'      => (float) ($this->monto_pagado ?? 0),
            'monto_pendiente'   => round(max(0, $montoPendiente), 4),
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'fecha_pago'        => $this->fecha_pago_efectivo,
            'estado'            => $this->estado,
            'venta_id'          => $this->venta_id,
            'plan_cuotas_id'    => $this->plan_cuotas_id,
        ];
    }
}
