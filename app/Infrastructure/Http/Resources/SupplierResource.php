<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'razon_social'    => $this->razon_social,
            'nombre_fantasia' => $this->nombre_fantasia,
            'ruc_rut_nit'     => $this->ruc_rut_nit,
            'pais'            => $this->pais,
            'tipo'            => $this->tipo,
            'moneda_principal'=> $this->moneda_principal,
            'email'           => $this->email,
            'telefono'        => $this->telefono,
            'activo'          => (bool) $this->activo,
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
