<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanCuotasRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tipo_plan'            => 'required|in:FRANCESA,ALEMANA,MANUAL',
            'moneda'               => 'required|in:USD,PYG,BRL',
            'capital_total'        => 'required|numeric|min:0',
            'capital_total_usd'    => 'required|numeric|min:0',
            'numero_cuotas'        => 'nullable|integer|min:1|max:120',
            'tasa_interes_mensual' => 'nullable|numeric|min:0|max:10',
            'fecha_primera_cuota'  => 'nullable|date|after:today',
            'refuerzo_cada'        => 'nullable|integer|min:0',
            'refuerzo_monto'       => 'nullable|numeric|min:0',
            'entregas'             => 'nullable|array',
            'entregas.*.tipo'      => 'required|string',
            'entregas.*.monto_usd' => 'required|numeric|min:0',
            'cuotas_manual'        => 'nullable|array',
            'cuotas_manual.*.monto'=> 'required|numeric|min:0.01',
            'cuotas_manual.*.fecha'=> 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'capital_total.min'              => 'El capital del plan debe ser mayor a cero.',
            'tasa_interes_mensual.max'       => 'La tasa mensual no puede superar el 10%.',
            'cuotas_manual.*.monto.min'      => 'Cada cuota manual debe tener monto mayor a cero.',
            'cuotas_manual.*.fecha.required' => 'Cada cuota manual requiere fecha de vencimiento.',
        ];
    }
}
