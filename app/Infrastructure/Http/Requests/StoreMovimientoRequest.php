<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tipo'     => 'required|in:INGRESO,EGRESO',
            'concepto' => 'required|string|max:300',
            'moneda'   => 'required|in:USD,PYG,BRL',
            'monto'    => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in'       => 'El tipo de movimiento debe ser INGRESO o EGRESO.',
            'monto.min'     => 'El monto debe ser mayor a cero.',
            'monto.numeric' => 'El monto debe ser un número válido.',
        ];
    }
}
