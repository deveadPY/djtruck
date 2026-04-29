<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGastoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'concepto'          => 'required|string|max:255',
            'descripcion'       => 'nullable|string|max:1000',
            'categoria'         => 'required|string|max:100',
            'origen_tipo'       => 'required|string|max:100',
            'moneda'            => 'required|in:USD,PYG,BRL',
            'monto_moneda'      => 'required|numeric|min:0',
            'monto_usd'         => 'required|numeric|min:0',
            'fecha_gasto'       => 'required|date',
            'repuesto_id'       => 'nullable|integer|exists:stock_repuestos,id',
            'repuesto_cantidad'  => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'concepto.required'      => 'El concepto del gasto es obligatorio.',
            'monto_usd.min'          => 'El monto en USD debe ser mayor o igual a cero.',
            'fecha_gasto.required'   => 'La fecha del gasto es obligatoria.',
        ];
    }
}
