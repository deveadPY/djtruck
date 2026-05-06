<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ruc_rut_nit'      => 'required|string|max:30',
            'razon_social'     => 'required|string|max:200',
            'nombre_fantasia'  => 'nullable|string|max:200',
            'pais'             => 'required|string|size:2',
            'tipo'             => 'required|in:FABRICANTE,DISTRIBUIDOR,IMPORTADOR,SERVICIO,OTRO',
            'moneda_principal' => 'required|in:USD,PYG,BRL',
            'email'            => 'nullable|email|max:150',
            'telefono'         => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in'             => 'El tipo de proveedor seleccionado no es válido.',
            'moneda_principal.in' => 'La moneda principal debe ser USD, PYG o BRL.',
        ];
    }
}
