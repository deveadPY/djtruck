<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use App\Infrastructure\Currency\LaMonedaRateSync;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El costo se carga una sola vez en su moneda: el USD lo calcula el servidor con la
     * cotización (USD → tasa 1). Sin cotización válida queda vacío y la validación lo marca.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];
        if (is_string($this->input('numero_chasis'))) {
            $merge['numero_chasis'] = strtoupper(trim($this->input('numero_chasis')));
        }

        $costo = $this->input('costo_origen_moneda');
        if (is_numeric($costo)) {
            $tasa = $this->input('tasa_cambio_compra');
            if ($this->input('moneda_costo', 'USD') === 'USD') {
                $merge['tasa_cambio_compra'] = 1;
                $merge['costo_origen_usd'] = round((float) $costo, 2);
            } else {
                $merge['costo_origen_usd'] = is_numeric($tasa) && (float) $tasa > 0 ? round((float) $costo / (float) $tasa, 2) : null;
            }
        }

        $this->merge($merge);
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
            'costo_origen_usd'    => 'required|numeric|min:0|max:99999999',
            'costo_origen_moneda' => 'required|numeric|min:0|max:999999999999',
            'proveedor_id'        => 'nullable|integer|exists:proveedores,id',
            'tasa_cambio_compra'  => ['required_unless:moneda_costo,USD', 'nullable', 'numeric', 'gt:0', function ($attr, $valor, $fail) {
                // Evita tipeos como 7.3 en vez de 7300 (el costo en USD se dispararía).
                [$min, $max] = LaMonedaRateSync::RANGOS[$this->input('moneda_costo')] ?? [0, INF];
                if ((float) $valor < $min || (float) $valor > $max) {
                    $fail("La cotización debe estar entre {$min} y {$max} {$this->input('moneda_costo')} por USD.");
                }
            }],
            'numero_motor'        => 'nullable|string|max:20',
            'precio_contado_usd'  => 'nullable|numeric|min:0|max:99999999',
            'precio_cuotas_usd'   => 'nullable|numeric|min:0|max:99999999',
            'precio_venta_sugerido_usd' => 'nullable|numeric|min:0|max:99999999',
            'margen_objetivo_pct' => 'nullable|numeric|min:0|max:1000',
            'valor_toma_usd'      => 'nullable|numeric|min:0|max:99999999',
            'kilometraje'         => 'nullable|integer|min:0|max:9999999',
            'observaciones'       => 'nullable|string|max:1000',
            'imagenes'            => 'nullable|array|max:10',
            'imagenes.*'          => 'image|max:5120',
            // Catálogo web
            'motor_descripcion'   => 'nullable|string|max:100',
            'potencia_hp'         => 'nullable|integer|min:0|max:9999',
            'par_nm'              => 'nullable|integer|min:0|max:99999',
            'tipo_traccion'       => 'nullable|in:4x2,4x4,6x2,6x4,8x4,6x6,8x8',
            'tipo_transmision'    => 'nullable|in:MANUAL,AUTOMATICA,AUTOMATIZADA',
            'cabina'              => 'nullable|string|max:80',
            'norma_euro'          => 'nullable|string|max:10',
            'peso_bruto_t'        => 'nullable|numeric|min:0|max:999',
            'deposito_litros'     => 'nullable|integer|min:0|max:9999',
            'neumaticos'          => 'nullable|string|max:50',
            'descripcion_publica' => 'nullable|string|max:2000',
            'equipamiento'        => 'nullable|array',
            'equipamiento.*'      => 'string|max:80',
            'publicar_en_web'     => 'nullable|boolean',
            'mostrar_precio'      => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'numero_chasis.unique'    => 'Ya existe un vehículo con ese número de chasis.',
            'numero_chasis.max'       => 'El número de chasis no puede superar 17 caracteres.',
            'costo_origen_usd.min'    => 'El costo en USD debe ser mayor a cero.',
            'costo_origen_usd.required' => 'Ingresá el costo del vehículo (y la cotización si no es en USD).',
            'tasa_cambio_compra.required_unless' => 'Falta la cotización del día para la moneda elegida.',
            'tipo_vehiculo.in'        => 'El tipo de vehículo seleccionado no es válido.',
            'estado.in'               => 'El estado seleccionado no es válido.',
            'imagenes.*.image'        => 'Cada archivo debe ser una imagen (JPG, PNG, GIF).',
            'imagenes.*.max'          => 'Cada imagen no puede superar 5MB.',
        ];
    }
}
