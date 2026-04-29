<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Application\Sales\DTOs\ProcessSaleData;
use App\Application\Sales\DTOs\SaleItemData;
use App\Application\Sales\DTOs\SalePaymentData;
use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Shared\Exceptions\InsufficientCreditException;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Services\ClienteCreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessSaleUseCase — orquesta la creación completa de una venta.
 *
 * Responsabilidades:
 *  1. Validar línea de crédito (si modalidad = CUOTAS)
 *  2. Calcular métricas financieras (valor libro, margen)
 *  3. Persistir venta, ítems, pagos, plan de cuotas en una transacción atómica
 *  4. Actualizar estado de vehículos y stock de repuestos
 *  5. Registrar movimientos en caja
 */
final class ProcessSaleUseCase
{
    public function __construct(
        private readonly CajaService          $cajaService,
        private readonly InstallmentGenerator $installmentGenerator,
        private readonly ClienteCreditService $creditService,
    ) {}

    /** @throws InsufficientCreditException|\Throwable */
    public function execute(ProcessSaleData $data): int
    {
        $this->validateCreditLine($data);

        DB::beginTransaction();
        try {
            $ventaId     = $this->insertVenta($data);
            $numeroVenta = $this->generateNumeroVenta($ventaId);

            $this->processItems($ventaId, $data->items, $data->tasaCambioVenta);
            $totalPagosIniciales = $this->processPayments($ventaId, $data, $numeroVenta);

            if ($data->modalidadPago === 'CUOTAS') {
                $this->processInstallmentPlan($ventaId, $data, $totalPagosIniciales);
            }

            DB::commit();
            return $ventaId;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ── Validaciones de negocio ───────────────────────────────────────────────

    private function validateCreditLine(ProcessSaleData $data): void
    {
        if ($data->modalidadPago !== 'CUOTAS') {
            return;
        }

        $cliente      = DB::table('clientes')->where('id', $data->clienteId)->first();
        $lineaCredito = (float) ($cliente->linea_credito_usd ?? 0);

        if ($lineaCredito <= 0) {
            return;
        }

        $precioFinal  = max(0, $data->precioVentaUsd - $data->descuentoUsd);
        $capitalEstimado = $data->capitalTotalUsd > 0
            ? $data->capitalTotalUsd
            : max(0, $precioFinal - array_sum(array_map(fn($p) => $p->montoUsd, $data->pagos)));

        $creditoDisponible = $this->creditService->creditoDisponibleUsd($data->clienteId, $lineaCredito);

        if ($capitalEstimado > $creditoDisponible) {
            throw InsufficientCreditException::forClient($data->clienteId, $capitalEstimado, $creditoDisponible);
        }
    }

    // ── Persistencia ─────────────────────────────────────────────────────────

    private function insertVenta(ProcessSaleData $data): int
    {
        $valorLibro = array_sum(array_map(
            fn(SaleItemData $i) => $i->costoSnapshotUsd * $i->cantidad,
            $data->items
        ));

        $precioNeto    = max(0, $data->precioVentaUsd - $data->descuentoUsd);
        $margenBruto   = round($precioNeto - $valorLibro, 4);
        $margenPct     = $valorLibro > 0 ? round(($margenBruto / $valorLibro) * 100, 4) : 0;

        return DB::table('ventas')->insertGetId([
            'numero_venta'          => '',          // se actualiza justo después
            'vehiculo_id'           => $data->vehiculoId,
            'cliente_id'            => $data->clienteId,
            'fecha_venta'           => $data->fechaVenta,
            'moneda_venta'          => $data->monedaVenta,
            'precio_venta_moneda'   => $data->precioVentaMoneda,
            'precio_venta_usd'      => $data->precioVentaUsd,
            'tasa_cambio_venta'     => $data->tasaCambioVenta,
            'estado'                => $data->estado,
            'observaciones'         => $data->observaciones,
            'modalidad_pago'        => $data->modalidadPago,
            'descuento_moneda'      => $data->descuentoMoneda,
            'descuento_usd'         => $data->descuentoUsd,
            'valor_libro_snapshot'  => $valorLibro,
            'margen_bruto_usd'      => $margenBruto,
            'margen_pct'            => $margenPct,
            'vendedor_id'           => $data->vendedorId,
            'created_by'            => $data->vendedorId,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    private function generateNumeroVenta(int $ventaId): string
    {
        $numero = 'V-' . date('Ym') . '-' . str_pad((string) $ventaId, 4, '0', STR_PAD_LEFT);
        DB::table('ventas')->where('id', $ventaId)->update(['numero_venta' => $numero]);
        return $numero;
    }

    /** @param SaleItemData[] $items */
    private function processItems(int $ventaId, array $items, float $tasa): void
    {
        foreach ($items as $item) {
            $precioMoneda   = $item->precioUnitarioUsd * $tasa;

            DB::table('venta_items')->insert([
                'venta_id'               => $ventaId,
                'itemable_id'            => $item->itemableId,
                'itemable_type'          => $item->itemableType,
                'descripcion'            => $item->descripcion,
                'cantidad'               => $item->cantidad,
                'precio_unitario_moneda' => $precioMoneda,
                'precio_unitario_usd'    => $item->precioUnitarioUsd,
                'subtotal_moneda'        => $precioMoneda * $item->cantidad,
                'subtotal_usd'           => $item->precioUnitarioUsd * $item->cantidad,
                'costo_snapshot_usd'     => $item->costoSnapshotUsd,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            if ($item->itemableType === 'App\\Models\\Vehicle') {
                DB::table('vehiculos')
                    ->where('id', $item->itemableId)
                    ->update(['estado' => 'VENDIDO', 'updated_at' => now()]);
            }

            if ($item->itemableType === 'App\\Models\\StockRepuesto') {
                DB::table('stock_repuestos')
                    ->where('id', $item->itemableId)
                    ->decrement('stock_actual', $item->cantidad);
            }
        }
    }

    /** @param SalePaymentData[] $pagos */
    private function processPayments(int $ventaId, ProcessSaleData $data, string $numeroVenta): float
    {
        $totalInicialUsd = 0.0;

        foreach ($data->pagos as $pago) {
            $totalInicialUsd += $pago->montoUsd;

            DB::table('detalles_pago')->insert([
                'venta_id'            => $ventaId,
                'tipo_pago'           => $pago->tipo,
                'moneda'              => 'USD',
                'monto_moneda'        => $pago->montoUsd,
                'monto_usd'           => $pago->montoUsd,
                'tasa_cambio'         => 1,
                'vehiculo_canje_id'   => $pago->vehiculoCanjeId,
                'referencia_bancaria' => $pago->referencia,
                'fecha_pago'          => $data->fechaVenta,
                'observaciones'       => $data->modalidadPago === 'CUOTAS' ? 'Entrega inicial' : null,
                'created_by'          => $data->vendedorId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            if ($pago->vehiculoCanjeId !== null) {
                DB::table('vehiculos')
                    ->where('id', $pago->vehiculoCanjeId)
                    ->update(['estado' => 'TOMA', 'updated_at' => now()]);
            }

            if (in_array($pago->tipo, ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'TARJETA'], true)) {
                $label          = $this->tipoPagoLabel($pago->tipo);
                $modalidadLabel = $data->modalidadPago === 'CUOTAS' ? 'entrega inicial' : 'contado';
                try {
                    $this->cajaService->ingresoCapital(
                        "Venta {$numeroVenta} – {$label} ({$modalidadLabel})",
                        'USD', $pago->montoUsd, $pago->montoUsd, $ventaId, 'venta'
                    );
                } catch (\RuntimeException $e) {
                    Log::warning('ProcessSaleUseCase: no se pudo registrar en caja: ' . $e->getMessage());
                }
            }
        }

        return $totalInicialUsd;
    }

    private function processInstallmentPlan(int $ventaId, ProcessSaleData $data, float $totalPagosIniciales): void
    {
        $precioFinal     = max(0, $data->precioVentaUsd - $data->descuentoUsd);
        $capitalTotalUsd = $data->capitalTotalUsd > 0
            ? $data->capitalTotalUsd
            : max(0, $precioFinal - $totalPagosIniciales);

        $numeroCuotas = $data->tipoPlan === 'MANUAL' && count($data->cuotasManual) > 0
            ? count($data->cuotasManual)
            : $data->numeroCuotas;

        $planId = DB::table('planes_cuotas')->insertGetId([
            'venta_id'             => $ventaId,
            'cliente_id'           => $data->clienteId,
            'tipo_plan'            => $data->tipoPlan,
            'moneda'               => $data->monedaVenta,
            'capital_total'        => $capitalTotalUsd,
            'capital_total_usd'    => $capitalTotalUsd,
            'numero_cuotas'        => $numeroCuotas,
            'tasa_interes_mensual' => $data->tasaInteresMensual,
            'fecha_primera_cuota'  => $data->fechaPrimeraCuota,
            'estado'               => 'ACTIVO',
            'created_by'           => $data->vendedorId,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        if ($data->tipoPlan === 'MANUAL' && count($data->cuotasManual) > 0) {
            $this->installmentGenerator->generateManual($planId, $ventaId, $data->monedaVenta, $data->cuotasManual);
        } else {
            $moneda  = Currency::from($data->monedaVenta);
            $capital = new Money($capitalTotalUsd, $moneda);
            $this->installmentGenerator->generate(
                $planId, $ventaId,
                InstallmentPlan::from($data->tipoPlan),
                $capital,
                $numeroCuotas,
                $data->tasaInteresMensual,
                $data->fechaPrimeraCuota,
                $data->refuerzoCada,
                $data->refuerzoMonto,
            );
        }

        DB::table('detalles_pago')->insert([
            'venta_id'       => $ventaId,
            'tipo_pago'      => 'PLAN_CUOTAS',
            'moneda'         => $data->monedaVenta,
            'monto_moneda'   => $capitalTotalUsd,
            'monto_usd'      => $capitalTotalUsd,
            'tasa_cambio'    => 1,
            'plan_cuotas_id' => $planId,
            'fecha_pago'     => $data->fechaVenta,
            'created_by'     => $data->vendedorId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function tipoPagoLabel(string $tipo): string
    {
        return match ($tipo) {
            'EFECTIVO'      => 'Efectivo',
            'TRANSFERENCIA' => 'Transferencia',
            'CHEQUE'        => 'Cheque',
            'TARJETA'       => 'Tarjeta',
            default         => $tipo,
        };
    }
}
