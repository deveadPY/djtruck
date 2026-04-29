<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacturaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'proveedor_id'           => 'required|integer|exists:proveedores,id',
            'numero_factura'         => 'required|string|max:50',
            'fecha_factura'          => 'required|date',
            'destino'                => 'required|in:GASTO_OPERATIVO,VEHICULO,MIXTO',
            'vehiculo_id'            => 'required_if:destino,VEHICULO|nullable|integer|exists:vehiculos,id',
            'cuenta_gasto'           => 'nullable|string|max:100',
            'moneda'                 => 'required|in:USD,PYG,BRL',
            'subtotal'               => 'required|numeric|min:0',
            'impuestos'              => 'nullable|numeric|min:0',
            'total_usd'              => 'required|numeric|min:0',
            'estado'                 => 'required|in:PAGADA,PENDIENTE,VENCIDA',
            'descripcion'            => 'nullable|string|max:1000',
            'categoria_gasto'        => 'nullable|string|max:100',
            'documentos'             => 'nullable|array|max:10',
            'documentos.*'           => 'file|max:20480',
            'documentos_descripcion' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'vehiculo_id.required_if' => 'Debe seleccionar un vehículo cuando el destino es VEHICULO.',
            'total_usd.min'           => 'El total en USD debe ser mayor o igual a cero.',
        ];
    }
}
