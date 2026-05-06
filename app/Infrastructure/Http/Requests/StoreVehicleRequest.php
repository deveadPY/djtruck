<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_chasis'       => 'required|string|max:17|unique:vehiculos,numero_chasis',
            'marca'               => 'required|string|max:80',
            'modelo'              => 'required|string|max:80',
            'anio'                 => 'required|integer|min:1980|max:' . (date('Y') + 1),
            'color'               => 'nullable|string|max:50',
            'tipo_vehiculo'       => 'required|in:CAMION_RIGIDO,CAMION_TRACTO,SEMI_REMOLQUE,FURGON,VOLQUETE,CISTERNA,OTRO',
            'kilometraje'         => 'nullable|integer|min:0',
            'estado'              => 'required|in:EN_TRANSITO,EN_ADUANA,EN_PREPARACION,DISPONIBLE,RESERVADO,VENDIDO,TOMA,BAJA',
            'moneda_costo'        => 'required|in:USD,PYG,BRL',
            'costo_origen_usd'    => 'required|numeric|min:0',
            'costo_origen_moneda' => 'required|numeric|min:0',
            'proveedor_id'        => 'nullable|integer|exists:proveedores,id',
            'tasa_cambio_compra'  => 'nullable|numeric|min:0',
            'numero_motor'        => 'nullable|string|max:20',
            'precio_contado_usd'  => 'nullable|numeric|min:0',
            'precio_cuotas_usd'   => 'nullable|numeric|min:0',
            'observaciones'       => 'nullable|string|max:1000',
            'imagenes'            => 'nullable|array|max:10',
            'imagenes.*'          => 'image|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'numero_chasis.unique'    => 'Ya existe un vehículo con ese número de chasis.',
            'numero_chasis.max'       => 'El número de chasis no puede superar 17 caracteres.',
            'costo_origen_usd.min'    => 'El costo en USD debe ser mayor a cero.',
            'tipo_vehiculo.in'        => 'El tipo de vehículo seleccionado no es válido.',
            'estado.in'               => 'El estado seleccionado no es válido.',
            'imagenes.*.image'        => 'Cada archivo debe ser una imagen (JPG, PNG, GIF).',
            'imagenes.*.max'          => 'Cada imagen no puede superar 5MB.',
        ];
    }
}
