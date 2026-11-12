<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Services;

use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Vehicle\Events\VehicleTradeInReceived;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * Registra un vehículo recibido en canje como ingreso al inventario.
 *
 * El vehículo recibido entra al stock en EN_PREPARACION (pasa por taller antes
 * de quedar DISPONIBLE), vinculado a la venta de origen.
 */
class TradeInVehicleRegistrar
{
    public const ESTADO_INGRESO = 'EN_PREPARACION';

    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /** Campos que el usuario puede cargar del vehículo recibido (el valor de toma lo fija el sistema). */
    public const CAMPOS_VEHICULO = ['numero_chasis', 'numero_motor', 'marca', 'modelo', 'anio', 'color', 'kilometraje', 'tipo_vehiculo'];

    /** Alta desde la venta web: solo los campos permitidos + valor de toma = monto del pago en USD. */
    public function registerFromPayment(int $ventaOrigenId, array $canje, float $montoUsd): VehicleModel
    {
        return $this->register($ventaOrigenId, [
            ...Arr::only($canje, self::CAMPOS_VEHICULO),
            'numero_chasis' => strtoupper(trim((string) ($canje['numero_chasis'] ?? ''))),
            'valor_toma'  => $montoUsd,
            'moneda_toma' => 'USD',
        ]);
    }

    public function register(int $ventaOrigenId, array $tradeInPayload): VehicleModel
    {
        $monedaToma = Currency::from($tradeInPayload['moneda_toma'] ?? 'USD');
        $valorTomaUsd = $monedaToma === Currency::USD
            ? (float) $tradeInPayload['valor_toma']
            : $this->currencyConverter->toBaseCurrency($tradeInPayload['valor_toma'], $monedaToma)->amount;

        $vehicle = $this->vehicleRepository->create([
            'numero_chasis'        => $tradeInPayload['numero_chasis'],
            'numero_motor'         => $tradeInPayload['numero_motor'] ?? null,
            'marca'                => $tradeInPayload['marca'],
            'modelo'               => $tradeInPayload['modelo'],
            'anio'                 => $tradeInPayload['anio'],
            'color'                => $tradeInPayload['color'] ?? null,
            'kilometraje'          => $tradeInPayload['kilometraje'] ?? 0,
            'tipo_vehiculo'        => $tradeInPayload['tipo_vehiculo'] ?? 'CAMION_RIGIDO',
            'estado'               => self::ESTADO_INGRESO,
            'moneda_costo'         => $monedaToma->value,
            'costo_origen_usd'     => round($valorTomaUsd, 2),
            'costo_origen_moneda'  => $tradeInPayload['valor_toma'],
            'valor_toma_usd'       => round($valorTomaUsd, 2),
            'total_gastos_usd'     => 0,
            'venta_canje_origen_id' => $ventaOrigenId,
            'created_by'           => Auth::id(),
        ]);

        Event::dispatch(new VehicleTradeInReceived(
            vehicleId:       (int) $vehicle->id,
            saleOriginId:    $ventaOrigenId,
            valorTomaUsd:    (float) $valorTomaUsd,
        ));

        return $vehicle;
    }

    /**
     * Deshace la toma cuando la venta se anula o se quita el canje:
     * si el vehículo fue creado por esta venta, se da de baja lógica;
     * si ya existía en el sistema, vuelve a DISPONIBLE.
     * Si ya se vendió o reservó en otra operación, se bloquea (no se pierde esa venta).
     */
    public function release(int $vehiculoId, int $ventaId): void
    {
        $vehiculo = DB::table('vehiculos')->where('id', $vehiculoId)->whereNull('deleted_at')->lockForUpdate()->first();
        if (!$vehiculo) {
            return;
        }
        if (in_array($vehiculo->estado, ['VENDIDO', 'RESERVADO'], true)) {
            throw new RuntimeException(
                "El vehículo recibido {$vehiculo->marca} {$vehiculo->modelo} ({$vehiculo->numero_chasis}) ya está "
                . strtolower($vehiculo->estado) . ' en otra operación. Anulá primero esa venta.'
            );
        }
        $creadoPorVenta = (int) $vehiculo->venta_canje_origen_id === $ventaId;

        DB::table('vehiculos')->where('id', $vehiculoId)->update($creadoPorVenta
            ? ['deleted_at' => now(), 'deleted_by' => Auth::id(), 'updated_at' => now()]
            : ['estado' => 'DISPONIBLE', 'updated_at' => now()]);
    }
}
