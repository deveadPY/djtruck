<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
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
            'archivos'          => 'nullable|array|max:10',
            'archivos.*'        => 'file|max:20480',
        ];
    }

    public function messages(): array
    {
        return [
            'razon_social.required' => 'La razón social del cliente es obligatoria.',
            'pais.size'             => 'El código de país debe tener exactamente 2 caracteres (ej: PY, BR, AR).',
            'email.email'           => 'El formato del correo electrónico no es válido.',
            'linea_credito_usd.min' => 'La línea de crédito no puede ser negativa.',
            'archivos.*.max'        => 'Cada archivo no puede superar 20MB.',
        ];
    }
}
