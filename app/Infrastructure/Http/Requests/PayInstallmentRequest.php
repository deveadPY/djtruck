<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_pago'                 => 'required|date',
            'monto_pagado'               => 'required|numeric|min:0.01',
            'caja_id'                    => 'nullable|integer|exists:cajas,id',
            'observacion'                => 'nullable|string|max:500',
            // Descuento por anticipo (opcional)
            'aplicar_descuento_anticipo'  => 'nullable|boolean',
            'descuento_anticipo_pct'     => 'nullable|numeric|min:0|max:50',
            'descuento_proporcional'     => 'nullable|boolean',
            // Pago múltiple (opcional)
            'cuotas_ids'                 => 'nullable|array',
            'cuotas_ids.*'               => 'integer|exists:cuotas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_pago.required'   => 'La fecha de pago es obligatoria.',
            'monto_pagado.required' => 'El monto pagado es obligatorio.',
            'monto_pagado.min'      => 'El monto debe ser mayor a cero.',
        ];
    }
}
