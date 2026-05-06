<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Sales\ValueObjects\PaymentType;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\Exceptions\SaleAmountMismatchException;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SaleController extends BaseApiController
{
    public function __construct(
        private readonly CurrencyConverter    $currency,
        private readonly InstallmentGenerator $installments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = SaleModel::with(['vehiculo', 'cliente'])
            ->when($request->estado,     fn($q) => $q->where('estado', $request->estado))
            ->when($request->cliente_id, fn($q) => $q->where('cliente_id', $request->cliente_id));

        return $this->paginatedResponse($query->orderByDesc('created_at')->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        $sale = SaleModel::with(['vehiculo', 'detallesPago.vehiculoCanjeado', 'cuotas'])->findOrFail($id);
        return $this->successResponse($sale);
    }

    public function destroy(int $id): JsonResponse
    {
        $sale = SaleModel::findOrFail($id);
        if ($sale->estado === 'COMPLETADO') {
            return $this->errorResponse('No se puede eliminar una venta completada.', null, 409);
        }
        $sale->delete();
        return $this->successResponse(null, 'Venta eliminada.');
    }

    // =========================================================================
    // ★ VENTA HÍBRIDA CON CANJE
    // =========================================================================
    public function procesarVentaConCanje(Request $request): JsonResponse
    {
        $request->validate([
            'vehiculo_id'               => 'required|integer|exists:vehiculos,id',
            'cliente_id'                => 'required|integer',
            'vendedor_id'               => 'nullable|integer',
            'moneda_venta'              => 'required|in:USD,PYG,BRL',
            'precio_venta'              => 'required|numeric|min:1',
            'pagos'                     => 'required|array|min:1',
            'pagos.*.tipo'              => 'required|in:EFECTIVO,TRANSFERENCIA,CHEQUE,VEHICULO_CANJE,PLAN_CUOTAS,TARJETA',
            'pagos.*.monto'             => 'required|numeric|min:0.01',
            'pagos.*.moneda'            => 'required|in:USD,PYG,BRL',
            'pagos.*.caja_id'           => 'nullable|integer',
            // Datos del vehículo canjeado
            'pagos.*.vehiculo_canje.numero_chasis' => 'required_if:pagos.*.tipo,VEHICULO_CANJE|string',
            'pagos.*.vehiculo_canje.marca'         => 'required_if:pagos.*.tipo,VEHICULO_CANJE|string',
            'pagos.*.vehiculo_canje.modelo'        => 'required_if:pagos.*.tipo,VEHICULO_CANJE|string',
            'pagos.*.vehiculo_canje.anio'           => 'required_if:pagos.*.tipo,VEHICULO_CANJE|integer',
            'pagos.*.vehiculo_canje.valor_toma'    => 'required_if:pagos.*.tipo,VEHICULO_CANJE|numeric',
            // Plan de cuotas
            'pagos.*.plan.tipo'                    => 'required_if:pagos.*.tipo,PLAN_CUOTAS|in:FRANCESA,ALEMANA',
            'pagos.*.plan.numero_cuotas'           => 'required_if:pagos.*.tipo,PLAN_CUOTAS|integer|min:1|max:60',
            'pagos.*.plan.tasa_interes_mensual'    => 'required_if:pagos.*.tipo,PLAN_CUOTAS|numeric|min:0',
            'pagos.*.plan.fecha_primera_cuota'     => 'required_if:pagos.*.tipo,PLAN_CUOTAS|date',
            'observaciones'                        => 'nullable|string|max:1000',
        ]);

        // ── Validaciones de negocio ──────────────────────────────────────────
        $vehiculo      = VehicleModel::findOrFail($request->vehiculo_id);
        if (!$vehiculo->isDisponible()) {
            throw new InsufficientStockException(
                "Vehículo #{$vehiculo->id} no disponible. Estado: {$vehiculo->estado}"
            );
        }

        $monedaVenta   = Currency::from($request->moneda_venta);
        $precioUsd     = $monedaVenta === Currency::USD
            ? $request->precio_venta
            : $this->currency->toBaseCurrency($request->precio_venta, $monedaVenta)->amount;

        $this->validatePaymentSum($request->pagos, $precioUsd);

        $pagoEfectivo  = $this->findPago($request->pagos, 'EFECTIVO');
        $pagoCanje     = $this->findPago($request->pagos, 'VEHICULO_CANJE');
        $pagoCuotas    = $this->findPago($request->pagos, 'PLAN_CUOTAS');

        // ── Transacción atómica ─────────────────────────────────────────────
        $resultado = DB::transaction(function () use (
            $vehiculo, $request, $precioUsd, $monedaVenta,
            $pagoEfectivo, $pagoCanje, $pagoCuotas
        ) {
            // 1. Crear venta
            $venta = SaleModel::create([
                'numero_venta'        => $this->nextSaleNumber(),
                'cliente_id'          => $request->cliente_id,
                'vehiculo_id'         => $vehiculo->id,
                'vendedor_id'         => $request->vendedor_id ?? auth()->id(),
                'estado'              => 'EN_PROCESO',
                'moneda_venta'        => $request->moneda_venta,
                'precio_venta_moneda' => $request->precio_venta,
                'precio_venta_usd'    => $precioUsd,
                'valor_libro_snapshot'=> $vehiculo->valor_libro_usd,
                'observaciones'       => $request->observaciones,
                'fecha_venta'         => now()->toDateString(),
                'created_by'          => auth()->id(),
            ]);

            // 2. Pago efectivo
            if ($pagoEfectivo) {
                $this->registrarPagoEfectivo($venta->id, $pagoEfectivo);
            }

            // 3. Canje de vehículo → ingresa al stock
            $vehiculoCanjeado = null;
            if ($pagoCanje) {
                $vehiculoCanjeado = $this->ingresarVehiculoCanje($venta->id, $pagoCanje);
            }

            // 4. Plan de cuotas
            $planCuotas = null;
            if ($pagoCuotas) {
                $planCuotas = $this->crearPlanCuotas($venta->id, $request->cliente_id, $pagoCuotas);
            }

            // 5. Actualizar vehículo vendido
            $vehiculo->update(['estado' => 'VENDIDO', 'updated_by' => auth()->id()]);

            // 6. Guardar snapshots de tasas
            foreach ($request->pagos as $pago) {
                $m = Currency::from($pago['moneda'] ?? 'USD');
                if ($m !== Currency::USD) {
                    $this->currency->convert($pago['monto'], $m, Currency::USD, $venta->id, 'sale');
                }
            }

            // 7. Completar venta
            $venta->update(['estado' => 'COMPLETADO', 'updated_by' => auth()->id()]);

            return compact('venta', 'vehiculoCanjeado', 'planCuotas');
        });

        // ── Post-transacción: eventos ─────────────────────────────────────
        event(new \App\Domain\Sales\Events\SaleCompleted(
            $resultado['venta']->id,
            $vehiculo->id,
            $request->cliente_id,
            $precioUsd,
        ));

        return $this->successResponse([
            'venta'            => $resultado['venta']->fresh(['detallesPago', 'cuotas']),
            'vehiculo_canjeado' => $resultado['vehiculoCanjeado'],
            'plan_cuotas'      => $resultado['planCuotas'],
        ], 'Venta con canje procesada exitosamente.', 201);
    }

    public function profitability(int $id): JsonResponse
    {
        $venta = SaleModel::with('vehiculo.gastos')->findOrFail($id);
        return $this->successResponse([
            'venta_id'           => $id,
            'precio_venta_usd'   => $venta->precio_venta_usd,
            'valor_libro_usd'    => $venta->valor_libro_snapshot,
            'margen_bruto_usd'   => $venta->margen_bruto_usd,
            'margen_pct'         => round($venta->margen_pct, 2),
            'costo_origen_usd'   => $venta->vehiculo->costo_origen_usd,
            'total_gastos_usd'   => $venta->vehiculo->total_gastos_usd,
            'detalle_gastos'     => $venta->vehiculo->gastos->map(fn($g) => [
                'concepto'  => $g->concepto,
                'monto_usd' => $g->monto_usd,
                'fecha'     => $g->fecha_gasto,
            ]),
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function validatePaymentSum(array $pagos, float $precioUsd): void
    {
        $total = 0.0;
        foreach ($pagos as $p) {
            $m      = Currency::from($p['moneda'] ?? 'USD');
            $total += $m === Currency::USD
                ? $p['monto']
                : $this->currency->toBaseCurrency($p['monto'], $m)->amount;
        }
        if (abs($precioUsd - $total) > 0.01) {
            throw new SaleAmountMismatchException(
                sprintf('Suma de pagos (USD %.2f) ≠ precio venta (USD %.2f). Diferencia: %.4f', $total, $precioUsd, abs($precioUsd - $total))
            );
        }
    }

    private function registrarPagoEfectivo(int $ventaId, array $pago): void
    {
        $m   = Currency::from($pago['moneda'] ?? 'USD');
        $usd = $m === Currency::USD ? $pago['monto'] : $this->currency->toBaseCurrency($pago['monto'], $m)->amount;

        DB::table('detalles_pago')->insert([
            'venta_id'     => $ventaId,
            'tipo_pago'    => $pago['tipo'],
            'moneda'       => $m->value,
            'monto_moneda' => $pago['monto'],
            'monto_usd'    => $usd,
            'caja_id'      => $pago['caja_id'] ?? null,
            'fecha_pago'   => now()->toDateString(),
            'created_at'   => now(),
            'created_by'   => auth()->id(),
        ]);

        if (!empty($pago['caja_id'])) {
            DB::table('movimientos_caja')->insert([
                'caja_id'       => $pago['caja_id'],
                'tipo'          => 'INGRESO',
                'concepto'      => "Cobro venta #{$ventaId}",
                'referencia_id' => $ventaId,
                'ref_type'      => 'venta',
                'moneda'        => $m->value,
                'monto'         => $pago['monto'],
                'monto_usd'     => $usd,
                'created_at'    => now(),
                'created_by'    => auth()->id(),
            ]);
        }
    }

    private function ingresarVehiculoCanje(int $ventaId, array $pago): VehicleModel
    {
        $vc      = $pago['vehiculo_canje'];
        $m       = Currency::from($vc['moneda_toma'] ?? 'USD');
        $tomaUsd = $m === Currency::USD ? $vc['valor_toma'] : $this->currency->toBaseCurrency($vc['valor_toma'], $m)->amount;

        $nuevo = VehicleModel::create([
            'numero_chasis'       => $vc['numero_chasis'],
            'numero_motor'        => $vc['numero_motor'] ?? null,
            'marca'               => $vc['marca'],
            'modelo'              => $vc['modelo'],
            'anio'                 => $vc["anio"],
            'color'               => $vc['color'] ?? null,
            'kilometraje'         => $vc['kilometraje'] ?? 0,
            'tipo_vehiculo'       => $vc['tipo_vehiculo'] ?? 'CAMION_RIGIDO',
            'estado'              => 'TOMA',
            'moneda_costo'        => $m->value,
            'costo_origen_usd'    => $tomaUsd,
            'costo_origen_moneda' => $vc['valor_toma'],
            'valor_toma_usd'      => $tomaUsd,
            'total_gastos_usd'    => 0,
            'venta_canje_origen_id' => $ventaId,
            'created_by'          => auth()->id(),
        ]);

        DB::table('detalles_pago')->insert([
            'venta_id'          => $ventaId,
            'tipo_pago'         => 'VEHICULO_CANJE',
            'moneda'            => Currency::from($pago['moneda'] ?? 'USD')->value,
            'monto_moneda'      => $pago['monto'],
            'monto_usd'         => $tomaUsd,
            'vehiculo_canje_id' => $nuevo->id,
            'fecha_pago'        => now()->toDateString(),
            'observaciones'     => "{$vc['marca']} {$vc['modelo']} {$vc["anio"]}",
            'created_at'        => now(),
            'created_by'        => auth()->id(),
        ]);

        return $nuevo;
    }

    private function crearPlanCuotas(int $ventaId, int $clienteId, array $pago): array
    {
        $plan   = $pago['plan'];
        $m      = Currency::from($pago['moneda'] ?? 'USD');
        $capUsd = $m === Currency::USD ? $pago['monto'] : $this->currency->toBaseCurrency($pago['monto'], $m)->amount;

        $planId = DB::table('planes_cuotas')->insertGetId([
            'venta_id'              => $ventaId,
            'cliente_id'            => $clienteId,
            'tipo_plan'             => $plan['tipo'],
            'moneda'                => $m->value,
            'capital_total'         => $pago['monto'],
            'capital_total_usd'     => $capUsd,
            'numero_cuotas'         => $plan['numero_cuotas'],
            'tasa_interes_mensual'  => $plan['tasa_interes_mensual'] ?? 0,
            'fecha_primera_cuota'   => $plan['fecha_primera_cuota'],
            'estado'                => 'ACTIVO',
            'created_at'            => now(),
            'created_by'            => auth()->id(),
        ]);

        $cuotas = $this->installments->generate(
            planId:            $planId,
            ventaId:           $ventaId,
            tipo:              InstallmentPlan::from($plan['tipo']),
            capital:           new Money($pago['monto'], $m),
            numeroCuotas:      (int) $plan['numero_cuotas'],
            tasaMensual:       (float) ($plan['tasa_interes_mensual'] ?? 0),
            fechaPrimeraCuota: $plan['fecha_primera_cuota'],
        );

        DB::table('detalles_pago')->insert([
            'venta_id'       => $ventaId,
            'tipo_pago'      => 'PLAN_CUOTAS',
            'moneda'         => $m->value,
            'monto_moneda'   => $pago['monto'],
            'monto_usd'      => $capUsd,
            'plan_cuotas_id' => $planId,
            'fecha_pago'     => $plan['fecha_primera_cuota'],
            'created_at'     => now(),
            'created_by'     => auth()->id(),
        ]);

        return ['plan_id' => $planId, 'tipo' => $plan['tipo'], 'total_cuotas' => count($cuotas), 'cuotas' => $cuotas];
    }

    private function findPago(array $pagos, string $tipo): ?array
    {
        foreach ($pagos as $p) {
            if (($p['tipo'] ?? '') === $tipo) return $p;
        }
        return null;
    }

    private function nextSaleNumber(): string
    {
        $año = now()->year;
        $n   = SaleModel::withTrashed()->whereYear('created_at', $año)->lockForUpdate()->count();
        return sprintf('VTA-%d-%06d', $año, $n + 1);
    }
}
