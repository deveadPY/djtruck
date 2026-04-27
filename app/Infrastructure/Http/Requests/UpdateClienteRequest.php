<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'razon_social'      => 'required|string|max:200',
            'ruc'               => 'nullable|string|max:30',
            'nombre_fantasia'   => 'nullable|string|max:200',
            'pais'              => 'required|string|size:2',
            'email'             => 'nullable|email|max:150',
            'telefono'          => 'nullable|string|max:50',
            'direccion'         => 'nullable|string|max:300',
            'linea_credito_usd' => 'nullable|numeric|min:0',
            'activo'            => 'boolean',
        ];
    }
}
