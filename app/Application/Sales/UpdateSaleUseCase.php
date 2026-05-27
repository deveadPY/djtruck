<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Events\SaleCompleted;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\Validators\DuplicateVehicleSaleValidator;
use App\Domain\Sales\Validators\VehicleStateValidator;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UpdateSaleUseCase
{
    public function __construct(
        private readonly CajaService $cajaService,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly InstallmentGenerator $installmentGenerator,
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleStateValidator $vehicleStateValidator,
        private readonly DuplicateVehicleSaleValidator $duplicateSaleValidator
    ) {}

    public function execute(UpdateSaleDTO $dto): bool
    {
        $sale = $this->saleRepository->findById($dto->id);
        if (!$sale || $sale->deleted_at) {
            throw new RuntimeException('La venta no existe o ya fue cancelada.');
        }

        $oldValues = $sale->toArray();

        $this->duplicateSaleValidator->validateItems($dto->items, $dto->id);
        $this->vehicleStateValidator->validateItems($this->filterNewVehicleItems($dto->id, $dto->items));

        $result = DB::transaction(function () use ($dto, $sale, $oldValues) {
            $ventaId = $dto->id;

            // ═══════════════ FASE 1: REVERTIR ESTADO ANTERIOR ═══════════════

            // 1a. Revertir items: devolver vehículos a DISPONIBLE, retornar stock (solo items activos)
            $oldItems = DB::table('venta_items')->where('venta_id', $ventaId)->whereNull('deleted_at')->get();
            foreach ($oldItems as $item) {
                if (str_contains($item->itemable_type, 'Vehicle')) {
                    DB::table('vehiculos')->where('id', $item->itemable_id)->update([
                        'estado'     => 'DISPONIBLE',
                        'updated_at' => now(),
                    ]);
                }
                if (str_contains($item->itemable_type, 'Repuesto')) {
                    DB::table('stock_repuestos')->where('id', $item->itemable_id)->increment('stock_actual', $item->cantidad);
                }
            }

            // 1b. Soft-delete old items (solo items activos)
            DB::table('venta_items')
                ->where('venta_id', $ventaId)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            // 1c. Revertir pagos anteriores: anular cash movements
            DB::table('movimientos_caja')
                ->where('ref_type', 'venta')
                ->where('referencia_id', $ventaId)
                ->update([
                    'deleted_at' => now(),
                    'concepto'   => DB::raw("CONCAT('[ANULADO POR EDICIÓN] ', concepto)"),
                    'updated_at' => now(),
                ]);

            $recibosIds = DB::table('recibos_cuota')->where('venta_id', $ventaId)->pluck('id');
            if ($recibosIds->isNotEmpty()) {
                DB::table('movimientos_caja')
                    ->where('ref_type', 'recibo_cuota')
                    ->whereIn('referencia_id', $recibosIds->toArray())
                    ->update([
                        'deleted_at' => now(),
                        'concepto'   => DB::raw("CONCAT('[ANULADO POR EDICIÓN VENTA] ', concepto)"),
                        'updated_at' => now(),
                    ]);
                DB::table('recibos_cuota')
                    ->where('venta_id', $ventaId)
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);
            }

            // 1d. Desmarcar vehículos de canje
            DB::table('detalles_pago')
                ->where('venta_id', $ventaId)
                ->where('tipo_pago', 'VEHICULO_CANJE')
                ->whereNotNull('vehiculo_canje_id')
                ->pluck('vehiculo_canje_id')
                ->each(fn($vehiculoId) =>
                    DB::table('vehiculos')->where('id', $vehiculoId)->update([
                        'estado'     => 'DISPONIBLE',
                        'updated_at' => now(),
                    ])
                );

            // 1e. Soft-delete old payment details
            DB::table('detalles_pago')
                ->where('venta_id', $ventaId)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            // 1f. Cancelar plan de cuotas anterior si existe y está activo
            $oldPlan = DB::table('planes_cuotas')
                ->where('venta_id', $ventaId)
                ->where('estado', '!=', 'CANCELADO')
                ->first();
            if ($oldPlan) {
                DB::table('cuotas')
                    ->where('plan_cuotas_id', $oldPlan->id)
                    ->update([
                        'estado'     => 'CANCELADA',
                        'deleted_at' => now(),
                        'updated_at' => now(),
                    ]);
                DB::table('planes_cuotas')->where('id', $oldPlan->id)->update([
                    'estado'     => 'CANCELADO',
                    'updated_at' => now(),
                ]);
            }

            // ═══════════════ FASE 2: APLICAR NUEVO ESTADO ═══════════════════

            $modalidad = $dto->modalidadPago;
            $precioFinalUsd = max(0, $dto->precioVentaUsd - $dto->descuentoUsd);

            // Calcular valor libro y márgenes
            $valorLibroTotal = 0;
            foreach ($dto->items as $item) {
                $valorLibroTotal += (float)($item['costo_snapshot_usd'] ?? 0) * (float)$item['cantidad'];
            }
            $precioNeto = max(0, $dto->precioVentaUsd - $dto->descuentoUsd);
            $margenBruto = round($precioNeto - $valorLibroTotal, 4);
            $margenPct = $valorLibroTotal > 0 ? round(($margenBruto / $valorLibroTotal) * 100, 4) : 0;

            // 2a. Validar línea de crédito si es CUOTAS
            if ($modalidad === 'CUOTAS') {
                $this->validateCreditLimit($dto->clienteId, $precioFinalUsd, $dto);
            }

            // 2b. Actualizar registro de venta
            $this->saleRepository->update($ventaId, [
                'cliente_id'          => $dto->clienteId,
                'fecha_venta'         => $dto->fechaVenta,
                'moneda_venta'        => $dto->monedaVenta,
                'precio_venta_moneda' => $dto->precioVentaMoneda,
                'precio_venta_usd'    => $dto->precioVentaUsd,
                'modalidad_pago'      => $dto->modalidadPago,
                'estado'              => $dto->estado,
                'tasa_cambio_venta'   => $dto->tasaCambioVenta,
                'descuento_moneda'    => $dto->descuentoMoneda,
                'descuento_usd'       => $dto->descuentoUsd,
                'observaciones'       => $dto->observaciones,
                'valor_libro_snapshot'=> $valorLibroTotal,
                'margen_bruto_usd'    => $margenBruto,
                'margen_pct'          => $margenPct,
                'updated_by'          => Auth::id(),
                'updated_at'          => now(),
            ]);

            // 2c. Insertar nuevos items y aplicar efectos
            foreach ($dto->items as $item) {
                $precioMoneda = (float)$item['precio_unitario_usd'] * $dto->tasaCambioVenta;
                $subtotalMoneda = $precioMoneda * (float)$item['cantidad'];

                DB::table('venta_items')->insert([
                    'venta_id'              => $ventaId,
                    'itemable_id'           => $item['itemable_id'],
                    'itemable_type'         => $item['itemable_type'],
                    'descripcion'           => $item['descripcion'] ?? 'Item sin descripción',
                    'cantidad'              => $item['cantidad'],
                    'precio_unitario_moneda'=> $precioMoneda,
                    'precio_unitario_usd'   => $item['precio_unitario_usd'],
                    'subtotal_moneda'       => $subtotalMoneda,
                    'subtotal_usd'          => (float)$item['precio_unitario_usd'] * (float)$item['cantidad'],
                    'costo_snapshot_usd'    => $item['costo_snapshot_usd'] ?? 0,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                if (str_contains($item['itemable_type'], 'Vehicle')) {
                    DB::table('vehiculos')->where('id', $item['itemable_id'])->update([
                        'estado'     => 'VENDIDO',
                        'updated_at' => now(),
                    ]);
                }

                if (str_contains($item['itemable_type'], 'Repuesto')) {
                    DB::table('stock_repuestos')->where('id', $item['itemable_id'])->decrement('stock_actual', $item['cantidad']);
                }
            }

            // 2d. Procesar nuevos pagos
            $totalMontoInicialUsd = 0;
            foreach ($dto->pagos as $pago) {
                $montoUsd = floatval($pago['monto_usd'] ?? 0);
                if ($montoUsd <= 0) continue;

                $totalMontoInicialUsd += $montoUsd;

                DB::table('detalles_pago')->insert([
                    'venta_id'            => $ventaId,
                    'tipo_pago'           => $pago['tipo'] ?? 'EFECTIVO',
                    'moneda'              => 'USD',
                    'monto_moneda'        => $montoUsd,
                    'monto_usd'           => $montoUsd,
                    'tasa_cambio'         => 1,
                    'vehiculo_canje_id'   => ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id'])) ? $pago['vehiculo_canje_id'] : null,
                    'referencia_bancaria' => $pago['referencia'] ?? null,
                    'fecha_pago'          => $dto->fechaVenta,
                    'observaciones'       => $modalidad === 'CUOTAS' ? 'Entrega inicial' : null,
                    'created_by'          => Auth::id(),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                if ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id'])) {
                    DB::table('vehiculos')->where('id', $pago['vehiculo_canje_id'])->update([
                        'estado'     => 'TOMA',
                        'updated_at' => now(),
                    ]);
                }

                $this->registrarEnCaja($ventaId, $pago, $modalidad, $montoUsd);
            }

            // 2e. Procesar plan de cuotas si aplica
            if ($modalidad === 'CUOTAS') {
                $capitalTotalUsd = $dto->capitalTotalUsd ?? 0;
                if ($capitalTotalUsd <= 0) {
                    $capitalTotalUsd = max(0, $precioFinalUsd - $totalMontoInicialUsd);
                }

                $numeroCuotas = $dto->numeroCuotas ?? 12;
                $tipoPlan = $dto->tipoPlan ?? 'MANUAL';
                $cuotasManual = $dto->cuotasManual ?? [];

                $planId = DB::table('planes_cuotas')->insertGetId([
                    'venta_id'             => $ventaId,
                    'cliente_id'           => $dto->clienteId,
                    'tipo_plan'            => $tipoPlan,
                    'moneda'               => $dto->monedaVenta,
                    'capital_total'        => $capitalTotalUsd,
                    'capital_total_usd'    => $capitalTotalUsd,
                    'numero_cuotas'        => $numeroCuotas,
                    'tasa_interes_mensual' => $dto->tasaInteresMensual ?? 0,
                    'fecha_primera_cuota'  => $dto->fechaPrimeraCuota ?? now()->addMonth()->toDateString(),
                    'estado'               => 'ACTIVO',
                    'created_by'           => Auth::id(),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);

                if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
                    $this->generarCuotasManuales($planId, $ventaId, $dto->monedaVenta, $cuotasManual);
                } else {
                    $monedaEnum = Currency::from($dto->monedaVenta);
                    $capitalMoney = new Money($capitalTotalUsd, $monedaEnum);
                    $tipoPlanEnum = InstallmentPlan::from($tipoPlan);

                    $refuerzoCada = intval($dto->refuerzoCada ?? 0);
                    $refuerzoMontoVal = floatval($dto->refuerzoMonto ?? 0);
                    $refuerzoMoney = new Money($refuerzoMontoVal, $monedaEnum);

                    $generatedCuotas = $this->installmentGenerator->generate(
                        $planId,
                        $ventaId,
                        $tipoPlanEnum,
                        $capitalMoney,
                        $numeroCuotas,
                        (float)($dto->tasaInteresMensual ?? 0),
                        $dto->fechaPrimeraCuota ?? now()->addMonth()->toDateString(),
                        $refuerzoCada,
                        $refuerzoMoney
                    );

                    DB::table('planes_cuotas')->where('id', $planId)->update(['numero_cuotas' => count($generatedCuotas)]);
                }

                DB::table('detalles_pago')->insert([
                    'venta_id'        => $ventaId,
                    'tipo_pago'       => 'PLAN_CUOTAS',
                    'moneda'          => $dto->monedaVenta,
                    'monto_moneda'    => $capitalTotalUsd,
                    'monto_usd'       => $capitalTotalUsd,
                    'tasa_cambio'     => 1,
                    'plan_cuotas_id'  => $planId,
                    'fecha_pago'      => $dto->fechaVenta,
                    'created_by'      => Auth::id(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            // 2f. Auditoría
            $updatedSale = $this->saleRepository->findById($ventaId);
            $this->auditar(
                'UPDATE_SALE',
                'venta',
                $ventaId,
                $oldValues,
                $updatedSale ? $updatedSale->toArray() : [],
                Auth::id(),
                request()->ip(),
            );

            return true;
        });

        $updatedSale = $this->saleRepository->findById($dto->id);
        if ($updatedSale && in_array($updatedSale->estado, ['COMPLETADO', 'COMPLETADA'], true)
            && !in_array($oldValues['estado'] ?? '', ['COMPLETADO', 'COMPLETADA'], true)) {
            Event::dispatch(new SaleCompleted(
                saleId:    (int) $updatedSale->id,
                vehicleId: (int) $updatedSale->vehiculo_id,
                clienteId: (int) $updatedSale->cliente_id,
                totalUsd:  (float) $updatedSale->precio_venta_usd,
            ));
        }

        return $result;
    }

    /**
     * Devuelve items de tipo Vehicle que NO estaban ya en la venta (para evitar
     * revalidar items existentes que ya están marcados como VENDIDO).
     */
    private function filterNewVehicleItems(int $ventaId, array $items): array
    {
        $existingVehicleIds = DB::table('venta_items')
            ->where('venta_id', $ventaId)
            ->where('itemable_type', 'like', '%Vehicle%')
            ->pluck('itemable_id')
            ->toArray();

        return array_filter($items, function ($item) use ($existingVehicleIds) {
            $type = $item['itemable_type'] ?? '';
            if (!str_contains($type, 'Vehicle')) {
                return false;
            }
            return !in_array((int) ($item['itemable_id'] ?? 0), $existingVehicleIds, true);
        });
    }

    private function validateCreditLimit(int $clienteId, float $precioFinalUsd, UpdateSaleDTO $dto): void
    {
        $capitalTotalUsdEstimado = floatval($dto->capitalTotalUsd ?? 0);
        if ($capitalTotalUsdEstimado <= 0) {
            $totalPagosIniciales = array_sum(array_map(fn($p) => floatval($p['monto_usd'] ?? 0), $dto->pagos));
            $capitalTotalUsdEstimado = max(0, $precioFinalUsd - $totalPagosIniciales);
        }

        $cliente = DB::table('clientes')->where('id', $clienteId)->first();
        $lineaCredito = floatval($cliente->linea_credito_usd ?? 0);

        if ($lineaCredito > 0) {
            $saldoDeudor = DB::table('cuotas')
                ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
                ->where('planes_cuotas.cliente_id', $clienteId)
                ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
                ->whereNull('cuotas.deleted_at')
                ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));

            $creditoDisponible = $lineaCredito - (float) $saldoDeudor;

            if ($capitalTotalUsdEstimado > $creditoDisponible) {
                throw new RuntimeException(sprintf(
                    'El capital a financiar (USD %.2f) supera la línea de crédito disponible del cliente (USD %.2f).',
                    $capitalTotalUsdEstimado,
                    max(0, $creditoDisponible)
                ));
            }
        }
    }

    private function registrarEnCaja(int $ventaId, array $pago, string $modalidad, float $montoUsd): void
    {
        $tipoPagoActual = $pago['tipo'] ?? 'EFECTIVO';
        if (in_array($tipoPagoActual, ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'TARJETA'])) {
            $tipoLabel = match ($tipoPagoActual) {
                'EFECTIVO'      => 'Efectivo',
                'TRANSFERENCIA' => 'Transferencia',
                'CHEQUE'        => 'Cheque',
                'TARJETA'       => 'Tarjeta',
                default         => $tipoPagoActual,
            };
            $modalidadLabel = $modalidad === 'CUOTAS' ? 'entrega inicial' : 'contado';
            $numeroVenta = DB::table('ventas')->where('id', $ventaId)->value('numero_venta') ?: "V-$ventaId";
            try {
                $this->cajaService->ingresoCapital(
                    "Venta {$numeroVenta} [Editada] — {$tipoLabel} ({$modalidadLabel})",
                    'USD',
                    $montoUsd,
                    $montoUsd,
                    $ventaId,
                    'venta'
                );
            } catch (\RuntimeException $e) {
                Log::warning('UpdateSaleUseCase: no se pudo registrar movimiento en caja: ' . $e->getMessage());
            }
        }
    }

    private function generarCuotasManuales(int $planId, int $ventaId, string $moneda, array $cuotasManual): void
    {
        $cuotas = [];
        $i = 1;
        $totalCuotas = count($cuotasManual);

        foreach ($cuotasManual as $row) {
            $monto = floatval($row['monto'] ?? 0);
            if ($monto <= 0) continue;

            $cuotas[] = [
                'plan_cuotas_id'  => $planId,
                'venta_id'        => $ventaId,
                'numero_cuota'    => $i,
                'total_cuotas'    => $totalCuotas,
                'tipo_plan'       => 'MANUAL',
                'moneda'          => $moneda,
                'capital'         => round($monto, 4),
                'interes'         => 0,
                'fecha_vencimiento' => $row['fecha'] ?? now()->addMonths($i)->toDateString(),
                'estado'          => 'PENDIENTE',
                'monto_pagado'    => 0,
                'interes_mora'    => 0,
                'created_by'      => Auth::id(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
            $i++;
        }

        if (count($cuotas) > 0) {
            DB::table('cuotas')->insert($cuotas);
        }
    }

    private function auditar(
        string $action,
        string $entityType,
        int $entityId,
        array $oldValues,
        array $newValues,
        ?int $userId,
        ?string $ipAddress,
    ): void {
        DB::table('audit_logs')->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => json_encode($oldValues),
            'new_values'  => json_encode($newValues),
            'metadata'    => null,
            'ip_address'  => $ipAddress,
            'created_at'  => now(),
        ]);
    }
}
