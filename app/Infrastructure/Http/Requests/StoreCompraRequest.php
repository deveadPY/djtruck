<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'proveedor_id'                        => 'required|exists:proveedores,id',
            'fecha_compra'                        => 'required|date',
            'numero_factura'                      => 'nullable|string|max:50',
            'moneda_compra'                       => 'required|in:USD,PYG,BRL',
            'tasa_cambio'                         => 'required|numeric|min:1',
            'observaciones'                       => 'nullable|string|max:1000',
            'adjuntos'                            => 'nullable|array|max:5',
            'adjuntos.*'                          => 'file|mimes:pdf,jpg,jpeg,png|max:4096',
            'items'                               => 'required|array|min:1',
            'items.*.repuesto_id'                 => 'required|exists:stock_repuestos,id',
            'items.*.cantidad'                    => 'required|numeric|min:0.001',
            'items.*.precio_compra'               => 'required|numeric|min:0',
            'items.*.precio_venta_sugerido'       => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                => 'Debe agregar al menos un ítem a la compra.',
            'items.*.repuesto_id.exists'    => 'Uno de los repuestos seleccionados no existe.',
            'items.*.cantidad.min'          => 'La cantidad debe ser mayor a cero.',
            'adjuntos.*.max'                => 'Cada adjunto no puede superar 4 MB.',
        ];
    }
}
