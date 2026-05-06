<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Domain\Finance\Services\CajaService;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateSaleUseCase
{
    public function __construct(
        private readonly CajaService $cajaService,
        private readonly \App\Domain\Sales\Repositories\SaleRepositoryInterface $saleRepository
    ) {}

    /**
     * Procesa la creación de una venta completa.
     * 
     * @param array $data Datos validados de la venta
     * @return SaleModel
     * @throws \Exception
     */
    public function execute(array $data): SaleModel
    {
        $modalidad = $data['modalidad_pago'];
        $items = $data['items'] ?? [];
        $pagos = $data['pagos'] ?? [];

        // 1. Cálculos de Descuento y Precio Final
        $data['descuento_moneda'] = floatval($data['descuento_moneda'] ?? 0);
        $data['descuento_usd']    = floatval($data['descuento_usd'] ?? 0);
        $precioFinalUsd = max(0, $data['precio_venta_usd'] - $data['descuento_usd']);

        // 2. Validar línea de crédito para ventas a cuotas
        if ($modalidad === 'CUOTAS') {
            $this->validateCreditLimit($data['cliente_id'], $precioFinalUsd, $data, $pagos);
        }

        // 3. Preparar datos adicionales de la venta
        $valorLibroTotal = $this->calculateBookValue($items);
        $data['valor_libro_snapshot'] = $valorLibroTotal;
        $precioNeto = max(0, (float)$data['precio_venta_usd'] - (float)($data['descuento_usd'] ?? 0));
        $data['margen_bruto_usd'] = round($precioNeto - $valorLibroTotal, 4);
        $data['margen_pct']       = $valorLibroTotal > 0
            ? round(($data['margen_bruto_usd'] / $valorLibroTotal) * 100, 4)
            : 0;

        $data['vendedor_id'] = Auth::id();
        $data['created_by']  = Auth::id();
        $data['numero_venta'] = 'V-' . date('Ym') . '-' . str_pad(DB::table('ventas')->count() + 1, 4, '0', STR_PAD_LEFT);

        // Limpiar datos que no van en la tabla ventas directamente
        $tipoPlan = $data['tipo_plan'] ?? 'MANUAL';
        $capitalTotalUsd = floatval($data['capital_total_usd'] ?? 0);
        $cuotasManual = $data['cuotas_manual'] ?? [];
        $numeroCuotas = $data['numero_cuotas'] ?? 12;
        
        $ventaData = collect($data)->except([
            'tipo_plan', 'capital_total_usd', 'numero_cuotas', 'fecha_primera_cuota', 
            'items', 'pagos', 'cuotas_manual', 'modalidad_pago', 'tasa_interes_mensual',
            'refuerzo_cada', 'refuerzo_monto'
        ])->toArray();

        // Re-añadir modalidad_pago si es necesario
        $ventaData['modalidad_pago'] = $modalidad;

        return DB::transaction(function () use ($ventaData, $items, $pagos, $modalidad, $tipoPlan, $capitalTotalUsd, $cuotasManual, $numeroCuotas, $data, $precioFinalUsd) {
            // 4. Insertar Venta usando el Repositorio
            $venta = $this->saleRepository->create($ventaData + ['created_at' => now(), 'updated_at' => now()]);
            $ventaId = $venta->id;

            // 5. Procesar Items
            $this->processItems($ventaId, $items, $ventaData['tasa_cambio_venta'] ?? 1);

            // 6. Procesar Pagos Iniciales
            $totalMontoInicialUsd = $this->processPayments($venta, $pagos, $modalidad);

            // 7. Procesar Plan de Cuotas
            if ($modalidad === 'CUOTAS') {
                if ($capitalTotalUsd <= 0) {
                    $capitalTotalUsd = max(0, $precioFinalUsd - $totalMontoInicialUsd);
                }

                $planId = DB::table('planes_cuotas')->insertGetId([
                    'venta_id' => $ventaId,
                    'cliente_id' => $ventaData['cliente_id'],
                    'tipo_plan' => $tipoPlan,
                    'moneda' => $ventaData['moneda_venta'],
                    'capital_total' => $capitalTotalUsd,
                    'capital_total_usd' => $capitalTotalUsd,
                    'numero_cuotas' => $numeroCuotas,
                    'tasa_interes_mensual' => $data['tasa_interes_mensual'] ?? 0,
                    'fecha_primera_cuota' => $data['fecha_primera_cuota'] ?? now()->addMonth()->toDateString(),
                    'estado' => 'ACTIVO',
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
                    $this->generarCuotasManuales($planId, $ventaId, $ventaData['moneda_venta'], $cuotasManual);
                } else {
                    $this->generarCuotasAutomaticas($planId, $ventaId, $ventaData['moneda_venta'], $capitalTotalUsd, $data);
                }

                // Registrar detalle de pago por el plan
                DB::table('detalles_pago')->insert([
                    'venta_id' => $ventaId,
                    'tipo_pago' => 'PLAN_CUOTAS',
                    'moneda' => $ventaData['moneda_venta'],
                    'monto_moneda' => $capitalTotalUsd,
                    'monto_usd' => $capitalTotalUsd,
                    'tasa_cambio' => 1,
                    'plan_cuotas_id' => $planId,
                    'fecha_pago' => $ventaData['fecha_venta'],
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $venta;
        });
    }

    private function validateCreditLimit(int $clienteId, float $precioFinalUsd, array $data, array $pagos): void
    {
        $capitalTotalUsdEstimado = floatval($data['capital_total_usd'] ?? 0);
        if ($capitalTotalUsdEstimado <= 0) {
            $totalPagosIniciales = array_sum(array_map(fn($p) => floatval($p['monto_usd'] ?? 0), $pagos));
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
                throw new \RuntimeException(sprintf(
                    'El capital a financiar (USD %.2f) supera la línea de crédito disponible del cliente (USD %.2f).',
                    $capitalTotalUsdEstimado,
                    max(0, $creditoDisponible)
                ));
            }
        }
    }

    private function calculateBookValue(array $items): float
    {
        $valorLibroTotal = 0;
        foreach ($items as $item) {
            $valorLibroTotal += (float)($item['costo_snapshot_usd'] ?? 0) * (float)$item['cantidad'];
        }
        return $valorLibroTotal;
    }

    private function processItems(int $ventaId, array $items, float $tasaConversion): void
    {
        foreach ($items as $item) {
            $precioMoneda = (float)$item['precio_unitario_usd'] * $tasaConversion;
            $subtotalMoneda = $precioMoneda * (float)$item['cantidad'];

            DB::table('venta_items')->insert([
                'venta_id' => $ventaId,
                'itemable_id' => $item['itemable_id'],
                'itemable_type' => $item['itemable_type'],
                'descripcion' => $item['descripcion'] ?? 'Item sin descripción',
                'cantidad' => $item['cantidad'],
                'precio_unitario_moneda' => $precioMoneda,
                'precio_unitario_usd' => $item['precio_unitario_usd'],
                'subtotal_moneda' => $subtotalMoneda,
                'subtotal_usd' => (float)$item['precio_unitario_usd'] * (float)$item['cantidad'],
                'costo_snapshot_usd' => $item['costo_snapshot_usd'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($item['itemable_type'] === 'App\\Models\\Vehicle' || str_contains($item['itemable_type'], 'VehicleModel')) {
                DB::table('vehiculos')->where('id', $item['itemable_id'])->update([
                    'estado' => 'VENDIDO',
                    'updated_at' => now(),
                ]);
            }

            if ($item['itemable_type'] === 'App\\Models\\StockRepuesto' || str_contains($item['itemable_type'], 'RepuestoModel')) {
                DB::table('stock_repuestos')->where('id', $item['itemable_id'])->decrement('stock_actual', $item['cantidad']);
            }
        }
    }

    private function processPayments(SaleModel $venta, array $pagos, string $modalidad): float
    {
        $totalMontoInicialUsd = 0;
        foreach ($pagos as $pago) {
            $montoUsd = floatval($pago['monto_usd'] ?? 0);
            if ($montoUsd <= 0) continue;

            $totalMontoInicialUsd += $montoUsd;

            DB::table('detalles_pago')->insert([
                'venta_id' => $venta->id,
                'tipo_pago' => $pago['tipo'] ?? 'EFECTIVO',
                'moneda' => 'USD',
                'monto_moneda' => $montoUsd,
                'monto_usd' => $montoUsd,
                'tasa_cambio' => 1,
                'vehiculo_canje_id' => ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id'])) ? $pago['vehiculo_canje_id'] : null,
                'referencia_bancaria' => $pago['referencia'] ?? null,
                'fecha_pago' => $venta->fecha_venta,
                'observaciones' => $modalidad === 'CUOTAS' ? 'Entrega inicial' : null,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id'])) {
                DB::table('vehiculos')->where('id', $pago['vehiculo_canje_id'])->update(['estado' => 'TOMA', 'updated_at' => now()]);
            }

            // Registrar ingreso en Caja
            $this->registrarEnCaja($venta, $pago, $modalidad, $montoUsd);
        }
        return $totalMontoInicialUsd;
    }

    private function registrarEnCaja(SaleModel $venta, array $pago, string $modalidad, float $montoUsd): void
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
            try {
                $this->cajaService->ingresoCapital(
                    "Venta {$venta->numero_venta} – {$tipoLabel} ({$modalidadLabel})",
                    'USD',
                    $montoUsd,
                    $montoUsd,
                    $venta->id,
                    'venta'
                );
            } catch (\RuntimeException $e) {
                Log::warning('CreateSaleUseCase: no se pudo registrar movimiento en caja: ' . $e->getMessage());
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
                'plan_cuotas_id' => $planId,
                'venta_id' => $ventaId,
                'numero_cuota' => $i,
                'total_cuotas' => $totalCuotas,
                'tipo_plan' => 'MANUAL',
                'moneda' => $moneda,
                'capital' => round($monto, 4),
                'interes' => 0,
                'fecha_vencimiento' => $row['fecha'] ?? now()->addMonths($i)->toDateString(),
                'estado' => 'PENDIENTE',
                'monto_pagado' => 0,
                'interes_mora' => 0,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $i++;
        }

        if (count($cuotas) > 0) {
            DB::table('cuotas')->insert($cuotas);
        }
    }

    private function generarCuotasAutomaticas(int $planId, int $ventaId, string $moneda, float $capital, array $data): void
    {
        $n = $data['numero_cuotas'] ?? 12;
        $tasa = ($data['tasa_interes_mensual'] ?? 0) / 100;
        $tipo = $data['tipo_plan'] ?? 'FRANCESA';
        $fecha = \Carbon\Carbon::parse($data['fecha_primera_cuota'] ?? now()->addMonth()->toDateString());

        $refuerzoCada = intval($data['refuerzo_cada'] ?? 0);
        $refuerzoMonto = floatval($data['refuerzo_monto'] ?? 0);

        $numRefuerzos = 0;
        if ($refuerzoCada > 0 && $refuerzoMonto > 0) {
            $numRefuerzos = intdiv($n, $refuerzoCada);
            $capital -= ($numRefuerzos * $refuerzoMonto);
        }

        $cuotas = [];
        $cuotaNumero = 0;
        $capitalRestante = $capital;

        for ($i = 1; $i <= $n; $i++) {
            $cuotaNumero++;
            $fechaCuota = $fecha->copy()->addMonths($i - 1)->toDateString();

            if ($tipo === 'FRANCESA') {
                $cuotaTotal = $tasa > 0 ? $capital * $tasa / (1 - pow(1 + $tasa, -$n)) : $capital / $n;
                $interes = $capitalRestante * $tasa;
                $cap = $cuotaTotal - $interes;
            } elseif ($tipo === 'ALEMANA') {
                $cap = $capital / $n;
                $interes = $capitalRestante * $tasa;
            } else {
                $cap = $capital / $n;
                $interes = 0;
            }

            $cuotas[] = [
                'plan_cuotas_id' => $planId,
                'venta_id' => $ventaId,
                'numero_cuota' => $cuotaNumero,
                'total_cuotas' => $n + $numRefuerzos,
                'tipo_plan' => $tipo,
                'moneda' => $moneda,
                'capital' => round($cap, 4),
                'interes' => round($interes, 4),
                'fecha_vencimiento' => $fechaCuota,
                'estado' => 'PENDIENTE',
                'monto_pagado' => 0,
                'interes_mora' => 0,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($tipo === 'ALEMANA') {
                $capitalRestante -= $cap;
            }

            if ($refuerzoCada > 0 && $refuerzoMonto > 0 && $i % $refuerzoCada === 0) {
                $cuotaNumero++;
                $cuotas[] = [
                    'plan_cuotas_id' => $planId,
                    'venta_id' => $ventaId,
                    'numero_cuota' => $cuotaNumero,
                    'total_cuotas' => $n + $numRefuerzos,
                    'tipo_plan' => $tipo,
                    'moneda' => $moneda,
                    'capital' => round($refuerzoMonto, 4),
                    'interes' => 0,
                    'fecha_vencimiento' => $fechaCuota,
                    'estado' => 'PENDIENTE',
                    'monto_pagado' => 0,
                    'interes_mora' => 0,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $totalFinal = count($cuotas);
        foreach ($cuotas as &$c) { $c['total_cuotas'] = $totalFinal; }

        DB::table('cuotas')->insert($cuotas);
        DB::table('planes_cuotas')->where('id', $planId)->update(['numero_cuotas' => $totalFinal]);
    }
}
