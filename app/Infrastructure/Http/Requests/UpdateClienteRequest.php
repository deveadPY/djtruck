<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ruc'   => $this->filled('ruc') ? trim((string) $this->ruc) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->email)) : null,
        ]);
    }

    public function rules(): array
    {
        // ID del cliente actual (excluido en validación unique)
        $clienteId = $this->route('id') ?? $this->route('cliente');

        return [
            'razon_social'      => 'required|string|max:200',
            'ruc'               => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('clientes', 'ruc')
                    ->ignore($clienteId)
                    ->whereNull('deleted_at'),
            ],
            'nombre_fantasia'   => 'nullable|string|max:200',
            'pais'              => 'required|string|size:2',
            'email'             => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('clientes', 'email')
                    ->ignore($clienteId)
                    ->whereNull('deleted_at'),
            ],
            'telefono'          => 'nullable|string|max:50',
            'direccion'         => 'nullable|string|max:300',
            'linea_credito_usd' => 'nullable|numeric|min:0',
            'activo'            => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'razon_social.required' => 'La razón social del cliente es obligatoria.',
            'pais.size'             => 'El código de país debe tener exactamente 2 caracteres (ej: PY, BR, AR).',
            'email.email'           => 'El formato del correo electrónico no es válido.',
            'email.unique'          => 'Ya existe otro cliente registrado con este correo electrónico.',
            'ruc.unique'            => 'Ya existe otro cliente registrado con este RUC/CI.',
            'linea_credito_usd.min' => 'La línea de crédito no puede ser negativa.',
        ];
    }
}
