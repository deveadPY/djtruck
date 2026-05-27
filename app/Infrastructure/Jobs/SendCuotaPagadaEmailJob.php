<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Infrastructure\Mail\EmailSenderService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendCuotaPagadaEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $cuotaId,
        private readonly int $userId
    ) {
        $this->queue = 'emails';
    }

    public function handle(EmailSenderService $mailer): void
    {
        $cuota = DB::table('cuotas')->where('id', $this->cuotaId)->first();
        if (!$cuota) {
            return;
        }

        $plan    = DB::table('planes_cuotas')->where('id', $cuota->plan_cuotas_id)->first();
        $venta   = DB::table('ventas')->where('id', $cuota->venta_id)->first();
        $cliente = DB::table('clientes')->where('id', $plan?->cliente_id)->first();

        if (!$cliente || !$cliente->email) {
            return;
        }

        $monto = (float) $cuota->capital + (float) $cuota->interes;

        $mailer->sendByTipo(
            tipo:     'RECIBO_CUOTA',
            toEmail:  $cliente->email,
            toNombre: $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
            vars: [
                'cliente_nombre'    => $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
                'numero_venta'      => $venta?->numero_venta ?? "#{$cuota->venta_id}",
                'numero_cuota'      => (string) $cuota->numero_cuota,
                'total_cuotas'      => (string) $cuota->total_cuotas,
                'monto_pagado'      => number_format($monto, 2, ',', '.'),
                'moneda'            => $cuota->moneda ?? 'USD',
                'fecha_pago'        => $cuota->fecha_pago_efectivo
                    ? Carbon::parse($cuota->fecha_pago_efectivo)->format('d/m/Y')
                    : Carbon::now()->format('d/m/Y'),
                'fecha_vencimiento' => Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y'),
            ],
            context: [
                'cliente_id'  => $plan?->cliente_id,
                'venta_id'    => $cuota->venta_id,
                'cuota_id'    => $this->cuotaId,
                'enviado_por' => $this->userId,
            ]
        );
    }
}
