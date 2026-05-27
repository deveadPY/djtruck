<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marca'                      => 'required|string|max:80',
            'modelo'                     => 'required|string|max:80',
            'anio'                        => 'required|integer|min:1980|max:' . (date('Y') + 1),
            'color'                      => 'nullable|string|max:50',
            'tipo_vehiculo'              => 'required|in:CAMION_RIGIDO,CAMION_TRACTO,SEMI_REMOLQUE,FURGON,VOLQUETE,CISTERNA,OTRO',
            'kilometraje'                => 'nullable|integer|min:0',
            'estado'                     => 'required|in:EN_TRANSITO,EN_ADUANA,EN_PREPARACION,DISPONIBLE,RESERVADO,VENDIDO,TOMA,BAJA',
            'moneda_costo'               => 'required|in:USD,PYG,BRL',
            'costo_origen_usd'           => 'required|numeric|min:0',
            'costo_origen_moneda'        => 'required|numeric|min:0',
            'proveedor_id'               => 'nullable|integer|exists:proveedores,id',
            'precio_venta_sugerido_usd'  => 'nullable|numeric|min:0',
            'precio_contado_usd'         => 'nullable|numeric|min:0',
            'precio_cuotas_usd'          => 'nullable|numeric|min:0',
            'imagenes'                   => 'nullable|array|max:10',
            'imagenes.*'                 => 'image|max:5120',
            // Catálogo web
            'motor_descripcion'          => 'nullable|string|max:100',
            'potencia_hp'                => 'nullable|integer|min:0|max:9999',
            'par_nm'                     => 'nullable|integer|min:0|max:99999',
            'tipo_traccion'              => 'nullable|in:4x2,4x4,6x2,6x4,8x4,6x6,8x8',
            'tipo_transmision'           => 'nullable|in:MANUAL,AUTOMATICA,AUTOMATIZADA',
            'cabina'                     => 'nullable|string|max:80',
            'norma_euro'                 => 'nullable|string|max:10',
            'peso_bruto_t'               => 'nullable|numeric|min:0|max:999',
            'deposito_litros'            => 'nullable|integer|min:0|max:9999',
            'neumaticos'                 => 'nullable|string|max:50',
            'descripcion_publica'        => 'nullable|string|max:2000',
            'equipamiento'               => 'nullable|array',
            'equipamiento.*'             => 'string|max:80',
            'publicar_en_web'            => 'nullable|boolean',
            'mostrar_precio'             => 'nullable|boolean',
        ];
    }
}
