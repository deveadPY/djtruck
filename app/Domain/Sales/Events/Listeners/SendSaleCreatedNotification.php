<?php

declare(strict_types=1);

namespace App\Domain\Sales\Events\Listeners;

use App\Domain\Sales\Events\SaleCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener para SaleCreated.
 * Registra una notificación interna y deja traza en el log de auditoría.
 * No envía email (eso lo hace SendSaleCompletedEmail cuando la venta cierra).
 */
class SendSaleCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';
    public int $tries = 3;
    public int $backoff = 30;

    public function handle(SaleCreated $event): void
    {
        try {
            $venta = DB::table('ventas')->where('id', $event->saleId)->first();
            if (!$venta) {
                return;
            }

            DB::table('notificaciones_enviadas')->insert([
                'usuario_id' => $venta->vendedor_id ?? null,
                'cuota_id'   => null,
                'asunto'     => "Nueva venta registrada — {$venta->numero_venta}",
                'contenido'  => sprintf(
                    'Venta %s creada por USD %.2f (modalidad: %s).',
                    $venta->numero_venta,
                    (float) $venta->precio_venta_usd,
                    $venta->modalidad_pago
                ),
                'leida'      => false,
                'enviada'    => false,
                'enviada_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('SaleCreated event processed', [
                'sale_id'    => $event->saleId,
                'vehicle_id' => $event->vehicleId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendSaleCreatedNotification falló', [
                'sale_id' => $event->saleId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
