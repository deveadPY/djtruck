<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Domain\Sales\Calculator\SaleCalculator;
use App\Domain\Sales\Events\SaleCompleted;
use App\Domain\Sales\Events\SaleCreated;
use App\Domain\Sales\Processors\InstallmentProcessor;
use App\Domain\Sales\Processors\PaymentProcessor;
use App\Domain\Sales\Processors\SaleItemProcessor;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Domain\Sales\Validators\CreditLimitValidator;
use App\Domain\Sales\Validators\DuplicateVehicleSaleValidator;
use App\Domain\Sales\Validators\InstallmentValidator;
use App\Domain\Sales\Validators\SaleIntegrityValidator;
use App\Domain\Sales\Validators\VehicleStateValidator;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class CreateSaleUseCase
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SaleIntegrityValidator $integrityValidator,
        private readonly CreditLimitValidator $creditValidator,
        private readonly InstallmentValidator $installmentValidator,
        private readonly SaleCalculator $calculator,
        private readonly SaleItemProcessor $itemProcessor,
        private readonly PaymentProcessor $paymentProcessor,
        private readonly InstallmentProcessor $installmentProcessor,
        private readonly VehicleStateValidator $vehicleStateValidator,
        private readonly DuplicateVehicleSaleValidator $duplicateSaleValidator
    ) {}

    public function execute(CreateSaleDTO $dto): SaleModel
    {
        $precioFinalUsd = $this->calculator->calculateFinalPrice($dto->precioVentaUsd, $dto->descuentoUsd);

        $this->integrityValidator->validate($dto->items, $dto->precioVentaUsd);

        $this->vehicleStateValidator->validateItems($dto->items);
        $this->duplicateSaleValidator->validateItems($dto->items);

        if ($dto->modalidadPago === 'CUOTAS') {
            $this->installmentValidator->validate(
                $dto->tipoPlan ?? 'MANUAL',
                $dto->numeroCuotas ?? 12,
                $dto->cuotasManual
            );

            $capitalEstimado = $this->estimateCapital($dto, $precioFinalUsd);
            $this->creditValidator->validate($dto->clienteId, $capitalEstimado);
        }

        $venta = DB::transaction(function () use ($dto, $precioFinalUsd) {
            $ventaData = $this->buildSaleData($dto, $precioFinalUsd);
            $venta = $this->saleRepository->create($ventaData);

            $this->itemProcessor->process(
                $venta->id,
                $dto->items,
                $dto->tasaCambioVenta ?? 1.0
            );

            $totalInicialUsd = $this->paymentProcessor->process(
                $venta,
                $dto->pagos,
                $dto->modalidadPago
            );

            if ($dto->modalidadPago === 'CUOTAS') {
                $this->processInstallmentPlan($venta, $dto, $precioFinalUsd, $totalInicialUsd);
            }

            return $venta;
        });

        Event::dispatch(new SaleCreated($venta->id, (int) $venta->vehiculo_id));

        if (in_array($venta->estado, ['COMPLETADO', 'COMPLETADA'], true)) {
            Event::dispatch(new SaleCompleted(
                saleId:    (int) $venta->id,
                vehicleId: (int) $venta->vehiculo_id,
                clienteId: (int) $venta->cliente_id,
                totalUsd:  (float) $venta->precio_venta_usd,
            ));
        }

        return $venta;
    }

    private function estimateCapital(CreateSaleDTO $dto, float $precioFinalUsd): float
    {
        $capitalDeclarado = (float) ($dto->capitalTotalUsd ?? 0);
        if ($capitalDeclarado > 0) {
            return $capitalDeclarado;
        }

        $totalPagosIniciales = $this->calculator->calculateInitialPaymentTotal($dto->pagos);
        return max(0, $precioFinalUsd - $totalPagosIniciales);
    }

    private function buildSaleData(CreateSaleDTO $dto, float $precioFinalUsd): array
    {
        $valorLibroTotal = $this->calculator->calculateBookValue($dto->items);
        $margenBruto = $this->calculator->calculateGrossMargin($precioFinalUsd, $valorLibroTotal);
        $margenPct = $this->calculator->calculateMarginPercentage($margenBruto, $valorLibroTotal);

        return [
            'cliente_id' => $dto->clienteId,
            'vehiculo_id' => $dto->resolveVehiculoPrincipal(),
            'fecha_venta' => $dto->fechaVenta,
            'moneda_venta' => $dto->monedaVenta,
            'precio_venta_moneda' => $dto->precioVentaMoneda,
            'precio_venta_usd' => $dto->precioVentaUsd,
            'modalidad_pago' => $dto->modalidadPago,
            'estado' => $dto->estado,
            'tasa_cambio_venta' => $dto->tasaCambioVenta,
            'descuento_moneda' => $dto->descuentoMoneda,
            'descuento_usd' => $dto->descuentoUsd,
            'observaciones' => $dto->observaciones,
            'valor_libro_snapshot' => $valorLibroTotal,
            'margen_bruto_usd' => $margenBruto,
            'margen_pct' => $margenPct,
            'vendedor_id' => Auth::id(),
            'created_by' => Auth::id(),
            'numero_venta' => $this->calculator->generateSaleNumber(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function processInstallmentPlan(
        SaleModel $venta,
        CreateSaleDTO $dto,
        float $precioFinalUsd,
        float $totalInicialUsd
    ): void {
        $capitalTotalUsd = (float) ($dto->capitalTotalUsd ?? 0);
        if ($capitalTotalUsd <= 0) {
            $capitalTotalUsd = max(0, $precioFinalUsd - $totalInicialUsd);
        }

        $this->installmentProcessor->process(
            ventaId: $venta->id,
            clienteId: $dto->clienteId,
            monedaVenta: $dto->monedaVenta,
            tipoPlan: $dto->tipoPlan ?? 'MANUAL',
            capitalTotalUsd: $capitalTotalUsd,
            numeroCuotas: $dto->numeroCuotas ?? 12,
            tasaInteresMensual: (float) ($dto->tasaInteresMensual ?? 0),
            fechaPrimeraCuota: $dto->fechaPrimeraCuota ?? now()->addMonth()->toDateString(),
            cuotasManual: $dto->cuotasManual ?? [],
            refuerzoCada: (int) ($dto->refuerzoCada ?? 0),
            refuerzoMonto: (float) ($dto->refuerzoMonto ?? 0),
            fechaVenta: $dto->fechaVenta
        );
    }
}
