<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pagos = $this->input('pagos');
        if (is_array($pagos)) {
            foreach ($pagos as $i => $pago) {
                if (isset($pago['canje']['numero_chasis']) && is_string($pago['canje']['numero_chasis'])) {
                    $pagos[$i]['canje']['numero_chasis'] = strtoupper(trim($pago['canje']['numero_chasis']));
                }
            }
            $this->merge(['pagos' => $pagos]);
        }
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'          => 'nullable|integer|exists:vehiculos,id',
            'cliente_id'           => 'required|integer|exists:clientes,id',
            'fecha_venta'          => 'required|date|before_or_equal:today',
            'moneda_venta'         => 'required|in:USD,PYG,BRL',
            'precio_venta_moneda'  => 'required|numeric|min:0.01|max:999999999999',
            'precio_venta_usd'     => 'required|numeric|min:0.01|max:99999999',
            'tasa_cambio_venta'    => 'nullable|numeric|min:0|max:99999999',
            'estado'               => 'required|in:PRESUPUESTO,RESERVADO,EN_PROCESO,COMPLETADO',
            'observaciones'        => 'nullable|string|max:1000',
            'modalidad_pago'       => 'required|in:CONTADO,CUOTAS',
            'descuento_moneda'     => 'nullable|numeric|min:0|max:999999999999',
            'descuento_usd'        => 'nullable|numeric|min:0|max:99999999',
            // Plan de cuotas
            'tipo_plan'            => 'nullable|in:FRANCESA,ALEMANA,MANUAL',
            'capital_total_usd'    => 'nullable|numeric|min:0|max:99999999',
            'numero_cuotas'        => 'nullable|integer|min:1|max:60',
            'tasa_interes_mensual' => 'nullable|numeric|min:0|max:10',
            'fecha_primera_cuota'  => 'nullable|date|after:fecha_venta',
            'refuerzo_cada'        => 'nullable|integer|min:0|max:60',
            'refuerzo_monto'       => 'nullable|numeric|min:0|max:99999999',
            // Items details
            'items'                 => 'required|array|min:1|max:100',
            'items.*.itemable_id'   => 'required|integer|min:1',
            'items.*.itemable_type' => 'required|string|max:100',
            'items.*.descripcion'   => 'nullable|string|max:255',
            'items.*.cantidad'      => 'required|numeric|min:0.01|max:99999',
            'items.*.precio_unitario_usd' => 'required|numeric|min:0|max:99999999',
            'items.*.costo_snapshot_usd'  => 'nullable|numeric|min:0|max:99999999',
            // Pagos
            'pagos'                       => 'nullable|array|max:20',
            'pagos.*.tipo'                => 'nullable|in:EFECTIVO,TRANSFERENCIA,CHEQUE,VEHICULO_CANJE,TARJETA',
            'pagos.*.monto_usd'           => 'nullable|numeric|min:0|max:99999999',
            // Al crear, el vehículo recibido se da de alta desde la venta; elegir uno existente solo al editar.
            'pagos.*.vehiculo_canje_id'   => $this->route('id') ? 'nullable|integer|exists:vehiculos,id' : 'prohibited',
            'pagos.*.canje'               => 'nullable|array',
            'pagos.*.canje.marca'         => 'nullable|required_with:pagos.*.canje.numero_chasis|string|max:80',
            'pagos.*.canje.modelo'        => 'nullable|required_with:pagos.*.canje.numero_chasis|string|max:80',
            'pagos.*.canje.anio'          => 'nullable|required_with:pagos.*.canje.numero_chasis|integer|min:1950|max:' . (date('Y') + 1),
            'pagos.*.canje.numero_chasis' => ['nullable', 'string', 'max:17', 'distinct',
                Rule::unique('vehiculos', 'numero_chasis')->whereNull('deleted_at')],
            'pagos.*.canje.kilometraje'   => 'nullable|integer|min:0|max:9999999',
            'pagos.*.referencia'          => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'vehiculo_id.exists'         => 'El vehículo seleccionado no existe.',
            'cliente_id.exists'          => 'El cliente seleccionado no existe.',
            'precio_venta_usd.min'       => 'El precio de venta debe ser mayor a cero.',
            'precio_venta_usd.max'       => 'El precio de venta supera el límite permitido (USD 99M).',
            'numero_cuotas.max'          => 'El plan no puede superar 60 cuotas.',
            'tasa_interes_mensual.max'   => 'La tasa de interés no puede superar el 10% mensual.',
            'fecha_primera_cuota.after'  => 'La primera cuota debe ser posterior a la fecha de venta.',
            'fecha_venta.before_or_equal'=> 'La fecha de venta no puede ser futura.',
            'items.max'                  => 'Una venta no puede tener más de 100 items.',
            'pagos.*.canje.numero_chasis.unique'   => 'Ya existe un vehículo con ese número de chasis.',
            'pagos.*.canje.numero_chasis.distinct' => 'El mismo chasis se cargó en dos pagos.',
            'pagos.*.vehiculo_canje_id.prohibited' => 'El vehículo recibido debe cargarse con sus datos.',
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

            // Un pago "vehículo parte de pago" exige el vehículo recibido (nuevo, o al editar uno ya vinculado)
            foreach ((array) $this->input('pagos', []) as $i => $pago) {
                if (($pago['tipo'] ?? null) !== 'VEHICULO_CANJE' || floatval($pago['monto_usd'] ?? 0) <= 0) {
                    continue;
                }
                if (empty($pago['vehiculo_canje_id']) && empty($pago['canje']['numero_chasis'])) {
                    $validator->errors()->add("pagos.$i.canje.numero_chasis", 'Cargá los datos del vehículo recibido como parte de pago (marca, modelo, año y chasis).');
                }
            }
        });
    }
}
