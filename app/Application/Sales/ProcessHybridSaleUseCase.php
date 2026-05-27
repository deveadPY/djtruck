<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Domain\Sales\Calculator\SaleCalculator;
use App\Domain\Sales\Events\SaleCompleted;
use App\Domain\Sales\Exceptions\InvalidVehicleStateException;
use App\Domain\Sales\Exceptions\SalePriceInconsistencyException;
use App\Domain\Sales\Processors\InstallmentProcessor;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Domain\Vehicle\Services\TradeInVehicleRegistrar;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Caso de uso: venta híbrida (efectivo + canje + cuotas en una sola operación).
 *
 * Orquesta validaciones, snapshot del vehículo principal, registro del canje,
 * creación del plan de cuotas y emisión del evento de venta completada.
 */
class ProcessHybridSaleUseCase
{
    private const TOLERANCIA_PAGOS = 0.01;

    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly TradeInVehicleRegistrar $tradeInRegistrar,
        private readonly InstallmentProcessor $installmentProcessor,
        private readonly SaleCalculator $calculator,
        private readonly CurrencyConverter $currency
    ) {}

    public function execute(ProcessHybridSaleDTO $dto): array
    {
        $vehiculo = $this->vehicleRepository->findById($dto->vehiculoId);
        $this->ensureVehicleAvailable($vehiculo);

        $monedaVenta = Currency::from($dto->monedaVenta);
        $precioUsd = $this->toUsd($dto->precioVenta, $monedaVenta);

        $this->ensurePaymentSumMatches($dto->pagos, $precioUsd);

        $result = DB::transaction(function () use ($dto, $vehiculo, $monedaVenta, $precioUsd) {
            $venta = $this->createSale($dto, $vehiculo, $monedaVenta, $precioUsd);

            $pagoEfectivo  = $dto->findPaymentByType('EFECTIVO');
            $pagoCanje     = $dto->findPaymentByType('VEHICULO_CANJE');
            $pagoCuotas    = $dto->findPaymentByType('PLAN_CUOTAS');

            if ($pagoEfectivo) {
                $this->registerCashPayment($venta->id, $pagoEfectivo);
            }

            $vehiculoCanjeado = null;
            if ($pagoCanje) {
                $vehiculoCanjeado = $this->registerTradeIn($venta->id, $pagoCanje);
            }

            $planCuotas = null;
            if ($pagoCuotas) {
                $planCuotas = $this->registerInstallmentPlan($venta, $dto, $pagoCuotas);
            }

            $this->markVehicleAsSold($vehiculo);
            $this->snapshotCurrencyRates($venta->id, $dto->pagos);
            $this->completeSale($venta);

            return [
                'venta'             => $venta,
                'vehiculoCanjeado'  => $vehiculoCanjeado,
                'planCuotas'        => $planCuotas,
            ];
        });

        Event::dispatch(new SaleCompleted(
            saleId:    (int) $result['venta']->id,
            vehicleId: (int) $vehiculo->id,
            clienteId: (int) $dto->clienteId,
            totalUsd:  (float) $precioUsd,
        ));

        return $result;
    }

    private function ensureVehicleAvailable(?VehicleModel $vehiculo): void
    {
        if (!$vehiculo) {
            throw InvalidVehicleStateException::notFound(0);
        }

        if (!$vehiculo->isDisponible()) {
            throw InvalidVehicleStateException::notAvailable(
                $vehiculo->id,
                $vehiculo->estado,
                $vehiculo->marca ?? '',
                $vehiculo->modelo ?? ''
            );
        }
    }

    private function ensurePaymentSumMatches(array $pagos, float $precioUsd): void
    {
        $total = 0.0;
        foreach ($pagos as $pago) {
            $moneda = Currency::from($pago['moneda'] ?? 'USD');
            $total += $this->toUsd((float) $pago['monto'], $moneda);
        }

        if (abs($precioUsd - $total) > self::TOLERANCIA_PAGOS) {
            throw SalePriceInconsistencyException::priceMismatch($precioUsd, $total);
        }
    }

    private function createSale(
        ProcessHybridSaleDTO $dto,
        VehicleModel $vehiculo,
        Currency $monedaVenta,
        float $precioUsd
    ): SaleModel {
        return $this->saleRepository->create([
            'numero_venta'         => $this->calculator->generateSaleNumber(),
            'cliente_id'           => $dto->clienteId,
            'vehiculo_id'          => $vehiculo->id,
            'vendedor_id'          => $dto->vendedorId ?? Auth::id(),
            'estado'               => 'EN_PROCESO',
            'moneda_venta'         => $monedaVenta->value,
            'precio_venta_moneda'  => $dto->precioVenta,
            'precio_venta_usd'     => $precioUsd,
            'valor_libro_snapshot' => $vehiculo->valor_libro_usd,
            'observaciones'        => $dto->observaciones,
            'fecha_venta'          => now()->toDateString(),
            'created_by'           => Auth::id(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    private function registerCashPayment(int $ventaId, array $pago): void
    {
        $moneda = Currency::from($pago['moneda'] ?? 'USD');
        $montoUsd = $this->toUsd((float) $pago['monto'], $moneda);

        $this->saleRepository->addPayment($ventaId, [
            'tipo_pago'    => $pago['tipo'],
            'moneda'       => $moneda->value,
            'monto_moneda' => $pago['monto'],
            'monto_usd'    => $montoUsd,
            'caja_id'      => $pago['caja_id'] ?? null,
            'fecha_pago'   => now()->toDateString(),
            'created_by'   => Auth::id(),
        ]);

        if (!empty($pago['caja_id'])) {
            DB::table('movimientos_caja')->insert([
                'caja_id'       => $pago['caja_id'],
                'tipo'          => 'INGRESO',
                'concepto'      => "Cobro venta #{$ventaId}",
                'referencia_id' => $ventaId,
                'ref_type'      => 'venta',
                'moneda'        => $moneda->value,
                'monto'         => $pago['monto'],
                'monto_usd'     => $montoUsd,
                'created_at'    => now(),
                'created_by'    => Auth::id(),
            ]);
        }
    }

    private function registerTradeIn(int $ventaId, array $pago): VehicleModel
    {
        $nuevo = $this->tradeInRegistrar->register($ventaId, $pago['vehiculo_canje']);

        $monedaPago = Currency::from($pago['moneda'] ?? 'USD');
        $tomaUsd = $this->toUsd((float) $pago['vehiculo_canje']['valor_toma'], $monedaPago);

        $this->saleRepository->addPayment($ventaId, [
            'tipo_pago'         => 'VEHICULO_CANJE',
            'moneda'            => $monedaPago->value,
            'monto_moneda'      => $pago['monto'],
            'monto_usd'         => $tomaUsd,
            'vehiculo_canje_id' => $nuevo->id,
            'fecha_pago'        => now()->toDateString(),
            'observaciones'     => "{$pago['vehiculo_canje']['marca']} {$pago['vehiculo_canje']['modelo']} {$pago['vehiculo_canje']['anio']}",
            'created_by'        => Auth::id(),
        ]);

        return $nuevo;
    }

    private function registerInstallmentPlan(SaleModel $venta, ProcessHybridSaleDTO $dto, array $pago): array
    {
        $plan = $pago['plan'];
        $monedaPago = Currency::from($pago['moneda'] ?? 'USD');
        $capitalUsd = $this->toUsd((float) $pago['monto'], $monedaPago);

        $planId = $this->installmentProcessor->process(
            ventaId:             $venta->id,
            clienteId:           $dto->clienteId,
            monedaVenta:         $monedaPago->value,
            tipoPlan:            $plan['tipo'],
            capitalTotalUsd:     $capitalUsd,
            numeroCuotas:        (int) $plan['numero_cuotas'],
            tasaInteresMensual:  (float) ($plan['tasa_interes_mensual'] ?? 0),
            fechaPrimeraCuota:   $plan['fecha_primera_cuota'],
            cuotasManual:        [],
            refuerzoCada:        0,
            refuerzoMonto:       0,
            fechaVenta:          $venta->fecha_venta,
        );

        return [
            'plan_id'      => $planId,
            'tipo'         => $plan['tipo'],
            'capital_usd'  => $capitalUsd,
            'numero_cuotas'=> $plan['numero_cuotas'],
        ];
    }

    private function markVehicleAsSold(VehicleModel $vehiculo): void
    {
        $vehiculo->update([
            'estado'     => 'VENDIDO',
            'updated_by' => Auth::id(),
        ]);
    }

    private function snapshotCurrencyRates(int $ventaId, array $pagos): void
    {
        foreach ($pagos as $pago) {
            $moneda = Currency::from($pago['moneda'] ?? 'USD');
            if ($moneda !== Currency::USD) {
                $this->currency->convert($pago['monto'], $moneda, Currency::USD, $ventaId, 'sale');
            }
        }
    }

    private function completeSale(SaleModel $venta): void
    {
        $venta->update([
            'estado'     => 'COMPLETADO',
            'updated_by' => Auth::id(),
        ]);
    }

    private function toUsd(float $monto, Currency $moneda): float
    {
        return $moneda === Currency::USD
            ? $monto
            : $this->currency->toBaseCurrency($monto, $moneda)->amount;
    }
}
