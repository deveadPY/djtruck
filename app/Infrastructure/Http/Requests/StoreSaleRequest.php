<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'          => 'nullable|integer|exists:vehiculos,id',
            'cliente_id'           => 'required|integer|exists:clientes,id',
            'fecha_venta'          => 'required|date',
            'moneda_venta'         => 'required|in:USD,PYG,BRL',
            'precio_venta_moneda'  => 'required|numeric|min:0',
            'precio_venta_usd'     => 'required|numeric|min:0',
            'tasa_cambio_venta'    => 'nullable|numeric|min:0',
            'estado'               => 'required|in:PRESUPUESTO,RESERVADO,EN_PROCESO,COMPLETADO',
            'observaciones'        => 'nullable|string|max:1000',
            'modalidad_pago'       => 'required|in:CONTADO,CUOTAS',
            'descuento_moneda'     => 'nullable|numeric|min:0',
            'descuento_usd'        => 'nullable|numeric|min:0',
            // Plan de cuotas
            'tipo_plan'            => 'nullable|in:FRANCESA,ALEMANA,MANUAL',
            'capital_total_usd'    => 'nullable|numeric|min:0',
            'numero_cuotas'        => 'nullable|integer|min:1|max:60',
            'tasa_interes_mensual' => 'nullable|numeric|min:0|max:10',
            'fecha_primera_cuota'  => 'nullable|date|after:fecha_venta',
            // Items details
            'items'                 => 'required|array|min:1',
            'items.*.itemable_id'   => 'required|integer',
            'items.*.itemable_type' => 'required|string',
            'items.*.cantidad'      => 'required|numeric|min:0.01',
            'items.*.precio_unitario_usd' => 'required|numeric|min:0',
            'items.*.costo_snapshot_usd'  => 'nullable|numeric'
        ];
    }

    public function messages(): array
    {
        return [
            'vehiculo_id.exists'         => 'El vehículo seleccionado no existe.',
            'cliente_id.exists'          => 'El cliente seleccionado no existe.',
            'precio_venta_usd.min'       => 'El precio de venta debe ser mayor a cero.',
            'numero_cuotas.max'          => 'El plan no puede superar 60 cuotas.',
            'tasa_interes_mensual.max'   => 'La tasa de interés no puede superar el 10% mensual.',
            'fecha_primera_cuota.after'  => 'La primera cuota debe ser posterior a la fecha de venta.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $descuentoUsd = floatval($this->input('descuento_usd', 0));
            $precioUsd    = floatval($this->input('precio_venta_usd', 0));

            if ($descuentoUsd > $precioUsd) {
                $validator->errors()->add('descuento_usd', 'El descuento no puede superar el precio de venta.');
            }
        });
    }
}
