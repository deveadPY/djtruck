<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Events\Listeners;

use App\Domain\Vehicle\Events\VehicleRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener para VehicleRegistered.
 * Registra entrada en audit_logs para historial de entradas de inventario.
 */
class LogVehicleRegistration implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(VehicleRegistered $event): void
    {
        try {
            $vehiculo = DB::table('vehiculos')->where('id', $event->vehicleId)->first();

            DB::table('audit_logs')->insert([
                'user_id'     => Auth::id(),
                'action'      => 'VEHICLE_REGISTERED',
                'entity_type' => 'vehiculo',
                'entity_id'   => $event->vehicleId,
                'old_values'  => null,
                'new_values'  => json_encode([
                    'chasis'         => $event->chasis,
                    'cost_origin_usd'=> $event->costOriginUsd,
                    'marca'          => $vehiculo->marca ?? null,
                    'modelo'         => $vehiculo->modelo ?? null,
                    'anio'           => $vehiculo->anio ?? null,
                ]),
                'metadata'    => json_encode(['source' => 'VehicleRegistered event']),
                'ip_address'  => request()?->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('LogVehicleRegistration falló', [
                'vehicle_id' => $event->vehicleId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
