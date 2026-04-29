<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepuestoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'codigo'             => 'required|string|max:50|unique:stock_repuestos,codigo',
            'descripcion'        => 'required|string|max:300',
            'marca_compatible'   => 'nullable|string|max:80',
            'unidad_medida'      => 'required|string|max:20',
            'stock_minimo'       => 'nullable|numeric|min:0',
            'costo_promedio_usd' => 'required|numeric|min:0',
            'precio_venta_usd'   => 'nullable|numeric|min:0',
            'proveedor_id'       => 'nullable|exists:proveedores,id',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique'           => 'Ya existe un repuesto con ese código.',
            'costo_promedio_usd.min'  => 'El costo promedio no puede ser negativo.',
        ];
    }
}
