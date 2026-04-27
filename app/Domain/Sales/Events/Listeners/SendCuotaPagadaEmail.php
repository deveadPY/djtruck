<?php

declare(strict_types=1);

namespace App\Domain\Sales\Events\Listeners;

use App\Infrastructure\Mail\EmailSenderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * NOT a true event listener — a service-style class called directly from
 * PlanCuotasWebController::pagarCuota() to send a payment receipt email.
 *
 * This avoids creating a new CuotaPagada domain event and keeps the change
 * contained to the controller.
 */
class SendCuotaPagadaEmail
{
    public function __construct(
        private readonly EmailSenderService $mailer
    ) {}

    public function sendRecibo(int $cuotaId, int $userId): void
    {
        $cuota   = DB::table('cuotas')->where('id', $cuotaId)->first();
        if (!$cuota) return;

        $plan    = DB::table('planes_cuotas')->where('id', $cuota->plan_cuotas_id)->first();
        if (!$plan) return;

        $venta   = DB::table('ventas')->where('id', $cuota->venta_id)->first();
        $cliente = DB::table('clientes')->where('id', $plan->cliente_id)->first();

        // Silent exit if no client email
        if (!$cliente || !$cliente->email) {
            return;
        }

        $monto = (float) $cuota->capital + (float) $cuota->interes;

        $vars = [
            'cliente_nombre'    => $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
            'numero_venta'      => $venta->numero_venta  ?? "#{$cuota->venta_id}",
            'numero_cuota'      => (string) $cuota->numero_cuota,
            'total_cuotas'      => (string) $cuota->total_cuotas,
            'monto_pagado'      => number_format($monto, 2, ',', '.'),
            'moneda'            => $cuota->moneda ?? 'USD',
            'fecha_pago'        => $cuota->fecha_pago_efectivo
                ? Carbon::parse($cuota->fecha_pago_efectivo)->format('d/m/Y')
                : Carbon::now()->format('d/m/Y'),
            'fecha_vencimiento' => Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y'),
        ];

        $this->mailer->sendByTipo(
            tipo:     'RECIBO_CUOTA',
            toEmail:  $cliente->email,
            toNombre: $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
            vars:     $vars,
            context:  [
                'cliente_id'  => $plan->cliente_id,
                'venta_id'    => $cuota->venta_id,
                'cuota_id'    => $cuotaId,
                'enviado_por' => $userId,
            ]
        );
    }
}
