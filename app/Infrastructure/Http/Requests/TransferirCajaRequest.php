<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferirCajaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'origen'   => 'required|in:CAJA_CHICA,CAJA_CAPITAL',
            'destino'  => 'required|in:CAJA_CHICA,CAJA_CAPITAL|different:origen',
            'moneda'   => 'required|in:USD,PYG,BRL',
            'monto'    => 'required|numeric|min:0.01',
            'concepto' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'destino.different' => 'La caja de origen y destino deben ser diferentes.',
            'monto.min'         => 'El monto de transferencia debe ser mayor a cero.',
        ];
    }
}
