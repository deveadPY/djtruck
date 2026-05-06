<?php

declare(strict_types=1);

namespace App\Domain\Sales\Events\Listeners;

use App\Domain\Sales\Events\InstallmentOverdue;
use App\Infrastructure\Mail\EmailSenderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendOverdueInstallmentNotification
{
    public function __construct(
        private readonly EmailSenderService $mailer
    ) {}

    public function handle(InstallmentOverdue $event): void
    {
        try {
            $cuota   = DB::table('cuotas')->where('id', $event->installmentId)->first();
            $venta   = DB::table('ventas')->where('id', $event->saleId)->first();
            $cliente = DB::table('clientes')->where('id', $event->clienteId)->first();

            if (!$cuota || !$venta || !$cliente) {
                return;
            }

            // Evitar notificar duplicadas el mismo día
            $yaNotificadaHoy = DB::table('notificaciones_enviadas')
                ->where('cuota_id', $event->installmentId)
                ->whereDate('enviada_en', now()->toDateString())
                ->exists();

            if ($yaNotificadaHoy) {
                return;
            }

            $asunto   = "Cuota vencida #{$cuota->numero_cuota} — {$venta->numero_venta}";
            $contenido = "Estimado/a {$cliente->razon_social}, su cuota #{$cuota->numero_cuota} de la venta {$venta->numero_venta} " .
                         "venció hace {$event->diasMora} día(s). Monto pendiente: USD " .
                         number_format((float) $cuota->capital + (float) $cuota->interes - (float) ($cuota->monto_pagado ?? 0), 2, ',', '.');

            // Registrar la notificación en BD siempre (para la campana)
            DB::table('notificaciones_enviadas')->insert([
                'usuario_id'  => null,
                'cuota_id'    => $event->installmentId,
                'asunto'      => $asunto,
                'contenido'   => $contenido,
                'leida'       => false,
                'enviada'     => false,
                'enviada_en'  => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Enviar email si el cliente tiene dirección
            if ($cliente->email) {
                $this->mailer->sendByTipo(
                    tipo:     'CUOTA_VENCIDA',
                    toEmail:  $cliente->email,
                    toNombre: $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
                    vars: [
                        'cliente_nombre'  => $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
                        'numero_venta'    => $venta->numero_venta ?? "#{$event->saleId}",
                        'numero_cuota'    => (string) $cuota->numero_cuota,
                        'dias_mora'       => (string) $event->diasMora,
                        'monto_pendiente' => number_format(
                            (float) $cuota->capital + (float) $cuota->interes - (float) ($cuota->monto_pagado ?? 0),
                            2, ',', '.'
                        ),
                    ],
                    context: [
                        'cliente_id' => $event->clienteId,
                        'cuota_id'   => $event->installmentId,
                        'enviado_por' => null,
                    ]
                );

                DB::table('notificaciones_enviadas')
                    ->where('cuota_id', $event->installmentId)
                    ->whereDate('enviada_en', now()->toDateString())
                    ->update(['enviada' => true]);
            }
        } catch (\Throwable $e) {
            Log::warning('SendOverdueInstallmentNotification falló', [
                'installment_id' => $event->installmentId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
