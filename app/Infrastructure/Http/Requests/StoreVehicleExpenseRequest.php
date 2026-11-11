<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concepto'          => 'required|string|max:255',
            'descripcion'       => 'nullable|string',
            'categoria'         => 'required|in:REPARACION_MECANICA,CHAPERIA_PINTURA,ELECTRICIDAD,NEUMATICOS,DERECHOS_ADUANA,IMPUESTO_IMPORTACION,LOGISTICA,DOCUMENTACION,OTROS_PREPARACION',
            'origen_tipo'       => 'required|in:FACTURA_PROVEEDOR,STOCK_REPUESTO,MANO_OBRA,ADUANA,FLETE,SEGURO,PATENTE_MATRICULA,OTRO',
            'moneda'            => 'required|string|in:USD,PYG,BRL',
            'tasa_cambio'       => 'nullable|numeric|min:0',
            'monto_moneda'      => 'required|numeric|min:0',
            'monto_usd'         => 'required|numeric|min:0',
            'fecha_gasto'       => 'required|date',
            'repuesto_id'       => 'nullable|integer|exists:stock_repuestos,id',
            'repuesto_cantidad' => 'nullable|numeric|min:0',
            'comprobante'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'comprobante.mimes' => 'El comprobante debe ser PDF o imagen (JPG, PNG, WEBP).',
            'comprobante.max'   => 'El comprobante no puede superar 5 MB.',
        ];
    }
}
