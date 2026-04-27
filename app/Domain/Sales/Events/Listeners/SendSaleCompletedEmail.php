<?php

declare(strict_types=1);

namespace App\Domain\Sales\Events\Listeners;

use App\Domain\Sales\Events\SaleCompleted;
use App\Infrastructure\Mail\EmailSenderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SendSaleCompletedEmail
{
    public function __construct(
        private readonly EmailSenderService $mailer
    ) {}

    public function handle(SaleCompleted $event): void
    {
        try {
            $venta    = DB::table('ventas')->where('id', $event->saleId)->first();
            $cliente  = DB::table('clientes')->where('id', $event->clienteId)->first();
            $vehiculo = DB::table('vehiculos')->where('id', $event->vehicleId)->first();

            // Silent exit if no client email — don't throw
            if (!$cliente || !$cliente->email) {
                return;
            }

            $vars = [
                'cliente_nombre'  => $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
                'numero_venta'    => $venta->numero_venta   ?? "#{$event->saleId}",
                'vehiculo_marca'  => $vehiculo->marca       ?? '',
                'vehiculo_modelo' => $vehiculo->modelo      ?? '',
                'vehiculo_anio'   => $vehiculo->año         ?? '',
                'total_usd'       => number_format((float) $event->totalUsd, 2, ',', '.'),
                'fecha_venta'     => $venta && $venta->fecha_venta
                    ? Carbon::parse($venta->fecha_venta)->format('d/m/Y')
                    : Carbon::now()->format('d/m/Y'),
            ];

            $this->mailer->sendByTipo(
                tipo:     'BIENVENIDA_VENTA',
                toEmail:  $cliente->email,
                toNombre: $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
                vars:     $vars,
                context:  [
                    'cliente_id'  => $event->clienteId,
                    'venta_id'    => $event->saleId,
                    'enviado_por' => null,  // system-triggered
                ]
            );
        } catch (\Throwable) {
            // Never let an email failure affect the sale flow
        }
    }
}
