<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Domain\Finance\Services\CajaService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Http\Requests\StoreSaleRequest;
use App\Infrastructure\Settings\EmpresaSettings;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaWebController extends Controller
{
    public function __construct(private readonly CajaService $cajaService) {}

    public function index(Request $request)
    {
        $q      = trim($request->input('q', ''));
        $estado = $request->input('estado', '');
        $desde  = $request->input('desde', '');
        $hasta  = $request->input('hasta', '');

        $ventas = DB::table('ventas')
            ->whereNull('ventas.deleted_at')
            ->leftJoin('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->leftJoin('vehiculos', 'ventas.vehiculo_id', '=', 'vehiculos.id')
            ->select(
                'ventas.*',
                'clientes.razon_social as cliente_nombre',
                'vehiculos.marca',
                'vehiculos.modelo',
                'vehiculos.numero_chasis'
            )
            ->when(filled($q), function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('ventas.numero_venta', 'like', $like)
                        ->orWhere('vehiculos.marca', 'like', $like)
                        ->orWhere('vehiculos.modelo', 'like', $like)
                        ->orWhere('vehiculos.numero_chasis', 'like', $like)
                        ->orWhere('clientes.razon_social', 'like', $like)
                        ->orWhere('clientes.nombre_fantasia', 'like', $like)
                        ->orWhereExists(function ($sub) use ($like) {
                            $sub->select(DB::raw(1))
                                ->from('venta_items')
                                ->whereColumn('venta_items.venta_id', 'ventas.id')
                                ->where('venta_items.descripcion', 'like', $like);
                        });
                });
            })
            ->when(filled($estado), fn($query) => $query->where('ventas.estado', $estado))
            ->when(filled($desde), fn($query) => $query->where('ventas.fecha_venta', '>=', $desde))
            ->when(filled($hasta), fn($query) => $query->where('ventas.fecha_venta', '<=', $hasta))
            ->latest('ventas.created_at')
            ->paginate(20)
            ->withQueryString();

        return view('ventas.index', compact('ventas', 'q', 'estado', 'desde', 'hasta'));
    }

    public function create()
    {
        $vehiculos = DB::table('vehiculos')
            ->whereIn('estado', ['DISPONIBLE', 'RESERVADO'])
            ->whereNull('deleted_at')
            ->get();

        // Cargar clientes con su crédito disponible calculado
        $clientes = DB::table('clientes')
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($cliente) {
                $saldoDeudor = DB::table('cuotas')
                    ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
                    ->where('planes_cuotas.cliente_id', $cliente->id)
                    ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
                    ->whereNull('cuotas.deleted_at')
                    ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));

                $cliente->credito_disponible_usd = max(0, floatval($cliente->linea_credito_usd ?? 0) - (float) $saldoDeudor);
                return $cliente;
            });

        $cajas = DB::table('cajas')->where('activo', true)->get();

        // Vehicles available for trade-in
        $vehiculos_canje = DB::table('vehiculos')
            ->whereIn('estado', ['DISPONIBLE', 'EN_PREPARACION', 'TOMA'])
            ->whereNull('deleted_at')
            ->get();

        // Repuestos available for sale
        $repuestos = DB::table('stock_repuestos')
            ->where('activo', true)
            ->where('stock_actual', '>', 0)
            ->whereNull('deleted_at')
            ->get();

        return view('ventas.create', compact('vehiculos', 'clientes', 'cajas', 'vehiculos_canje', 'repuestos'));
    }

    public function store(StoreSaleRequest $request)
    {
        $data = $request->validated();

        $modalidad = $data['modalidad_pago'];
        // modalidad_pago is kept in $data to be saved in the ventas table

        // ── Descuento ──────────────────────────────────────────────────────
        $data['descuento_moneda'] = floatval($data['descuento_moneda'] ?? 0);
        $data['descuento_usd']    = floatval($data['descuento_usd'] ?? 0);
        $precioFinalUsd = max(0, $data['precio_venta_usd'] - $data['descuento_usd']);

        // ── Validar línea de crédito para ventas a cuotas ─────────────────
        if ($modalidad === 'CUOTAS') {
            $capitalTotalUsdEstimado = floatval($request->input('capital_total_usd', 0));
            if ($capitalTotalUsdEstimado <= 0) {
                $totalPagosIniciales = array_sum(array_map(
                    fn($p) => floatval($p['monto_usd'] ?? 0),
                    $request->input('pagos', [])
                ));
                $capitalTotalUsdEstimado = max(0, $precioFinalUsd - $totalPagosIniciales);
            }

            $cliente = DB::table('clientes')->where('id', $data['cliente_id'])->first();
            $lineaCredito = floatval($cliente->linea_credito_usd ?? 0);

            if ($lineaCredito > 0) {
                // Calcular deuda activa del cliente
                $saldoDeudor = DB::table('cuotas')
                    ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
                    ->where('planes_cuotas.cliente_id', $data['cliente_id'])
                    ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
                    ->whereNull('cuotas.deleted_at')
                    ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));

                $creditoDisponible = $lineaCredito - (float) $saldoDeudor;

                if ($capitalTotalUsdEstimado > $creditoDisponible) {
                    return back()->withInput()->withErrors([
                        'capital_total_usd' => sprintf(
                            'El capital a financiar (USD %.2f) supera la línea de crédito disponible del cliente (USD %.2f).',
                            $capitalTotalUsdEstimado,
                            max(0, $creditoDisponible)
                        ),
                    ]);
                }
            }
        }

        // Remove plan data from main venta array
        $tipoPlan = $request->input('tipo_plan', 'MANUAL');
        $capitalTotalUsd = floatval($request->input('capital_total_usd', 0));
        unset($data['tipo_plan'], $data['capital_total_usd'], $data['numero_cuotas'], $data['fecha_primera_cuota'], $data['items']);

        // ── Processing Items (Cart) ─────────────────────────────────────────
        $items = $request->input('items', []);
        $valorLibroTotal = 0;
        foreach ($items as $item) {
            $valorLibroTotal += (float)($item['costo_snapshot_usd'] ?? 0) * (float)$item['cantidad'];
        }

        $data['valor_libro_snapshot'] = $valorLibroTotal;
        $precioNeto = max(0, (float)$data['precio_venta_usd'] - (float)($data['descuento_usd'] ?? 0));
        $data['margen_bruto_usd'] = round($precioNeto - $valorLibroTotal, 4);
        $data['margen_pct']       = $valorLibroTotal > 0
            ? round(($data['margen_bruto_usd'] / $valorLibroTotal) * 100, 4)
            : 0;

        $data['vendedor_id'] = Auth::id();
        $data['created_by']  = Auth::id();
        $data['numero_venta'] = 'V-' . date('Ym') . '-' . str_pad(DB::table('ventas')->count() + 1, 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $ventaId = DB::table('ventas')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

            // ── Save Sale Items ─────────────────────────────────────────────
            foreach ($items as $item) {
                $tasaConversion = (float)($data['tasa_cambio_venta'] ?? 1);
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

                // Update Vehicle status if it's a vehicle
                if ($item['itemable_type'] === 'App\\Models\\Vehicle') {
                    DB::table('vehiculos')->where('id', $item['itemable_id'])->update([
                        'estado' => 'VENDIDO',
                        'updated_at' => now(),
                    ]);
                }

                // Decrement Spare Parts stock if it's a repuesto
                if ($item['itemable_type'] === 'App\\Models\\StockRepuesto') {
                    DB::table('stock_repuestos')->where('id', $item['itemable_id'])->decrement('stock_actual', $item['cantidad']);
                }
            }

            // ── Process payments (for both CONTADO and CUOTAS as iniciales) ──
            $pagos = $request->input('pagos', []);
            $totalMontoInicialUsd = 0;

            foreach ($pagos as $pago) {
                $montoUsd = floatval($pago['monto_usd'] ?? 0);
                if ($montoUsd <= 0)
                    continue;

                $totalMontoInicialUsd += $montoUsd;

                DB::table('detalles_pago')->insert([
                    'venta_id' => $ventaId,
                    'tipo_pago' => $pago['tipo'] ?? 'EFECTIVO',
                    'moneda' => 'USD',
                    'monto_moneda' => $montoUsd, // assuming same for simplicity in this iteration
                    'monto_usd' => $montoUsd,
                    'tasa_cambio' => 1,
                    'vehiculo_canje_id' => ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id'])) ? $pago['vehiculo_canje_id'] : null,
                    'referencia_bancaria' => $pago['referencia'] ?? null,
                    'fecha_pago' => $data['fecha_venta'],
                    'observaciones' => $modalidad === 'CUOTAS' ? 'Entrega inicial' : null,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // If vehicle trade-in, mark that vehicle as TOMA
                if ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id'])) {
                    DB::table('vehiculos')->where('id', $pago['vehiculo_canje_id'])->update([
                        'estado' => 'TOMA',
                        'updated_at' => now(),
                    ]);
                }

                // ── Registrar ingreso en Caja Capital para pagos en efectivo ──
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
                            "Venta {$data['numero_venta']} – {$tipoLabel} ({$modalidadLabel})",
                            'USD',
                            $montoUsd,
                            $montoUsd,
                            $ventaId,
                            'venta'
                        );
                    } catch (\RuntimeException $e) {
                        Log::warning('VentaWebController: no se pudo registrar movimiento en caja: ' . $e->getMessage());
                    }
                }
            }

            // ── Process Cuotas Plan ──
            if ($modalidad === 'CUOTAS') {
                // If the frontend didn't supply capital, calculate it as Precio Final - Deposits
                if ($capitalTotalUsd <= 0) {
                    $capitalTotalUsd = max(0, $precioFinalUsd - $totalMontoInicialUsd);
                }

                $cuotasManual = $request->input('cuotas_manual', []);
                $numeroCuotas = $request->input('numero_cuotas', 12);

                if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
                    $numeroCuotas = count($cuotasManual);
                }

                $planId = DB::table('planes_cuotas')->insertGetId([
                    'venta_id' => $ventaId,
                    'cliente_id' => $data['cliente_id'],
                    'tipo_plan' => $tipoPlan,
                    'moneda' => $data['moneda_venta'],
                    'capital_total' => $capitalTotalUsd, // stored in USD for consistency in this module for now
                    'capital_total_usd' => $capitalTotalUsd,
                    'numero_cuotas' => $numeroCuotas,
                    'tasa_interes_mensual' => $request->input('tasa_interes_mensual', 0),
                    'fecha_primera_cuota' => $request->input('fecha_primera_cuota', now()->addMonth()->toDateString()),
                    'estado' => 'ACTIVO',
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Generar cuotas
                if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
                    $this->generarCuotasManuales($planId, $ventaId, $data['moneda_venta'], $cuotasManual);
                } else {
                    $this->generarCuotasAutomaticas($planId, $ventaId, $data['moneda_venta'], $capitalTotalUsd, $request);
                }

                // Register plan as a payment detail to balance the sale
                DB::table('detalles_pago')->insert([
                    'venta_id' => $ventaId,
                    'tipo_pago' => 'PLAN_CUOTAS',
                    'moneda' => $data['moneda_venta'],
                    'monto_moneda' => $capitalTotalUsd,
                    'monto_usd' => $capitalTotalUsd,
                    'tasa_cambio' => 1,
                    'plan_cuotas_id' => $planId,
                    'fecha_pago' => $data['fecha_venta'],
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('ventas.show', $ventaId)
                ->with('success', 'Venta y pagos registrados correctamente.')
                ->with('show_print_modal', true);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al registrar la venta: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $venta = DB::table('ventas')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $venta->vehiculo = DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first();
        $venta->cliente = DB::table('clientes')->where('id', $venta->cliente_id)->first();
        $venta->vendedor = DB::table('users')->where('id', $venta->vendedor_id)->first();
        $items = DB::table('venta_items')->where('venta_id', $id)->get();
        $pagos = DB::table('detalles_pago')->where('venta_id', $id)->whereNull('deleted_at')->get();
        $plan = DB::table('planes_cuotas')->where('venta_id', $id)->first();
        $cuotas = $plan ? DB::table('cuotas')->where('plan_cuotas_id', $plan->id)->whereNull('deleted_at')->orderBy('numero_cuota')->get() : collect();

        $precioFinalUsd = $venta->precio_venta_usd - ($venta->descuento_usd ?? 0);
        $rentabilidad = $precioFinalUsd - $venta->valor_libro_snapshot;

        $documentos = DB::table('documentos')
            ->where('documentable_type', 'ventas')
            ->where('documentable_id', $id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        return view('ventas.show', compact('venta', 'pagos', 'plan', 'cuotas', 'rentabilidad', 'documentos', 'items'));
    }

    public function imprimirNotaVenta($id)
    {
        try {
            $venta = DB::table('ventas')->where('id', $id)->firstOrFail();
            $vehiculo = DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first();
            $cliente = DB::table('clientes')->where('id', $venta->cliente_id)->first();
            $vendedor = DB::table('users')->where('id', $venta->vendedor_id)->first();
            
            $venta->vehiculo = $vehiculo;
            $venta->cliente = $cliente;
            $venta->vendedor = $vendedor;
            
            $items = DB::table('venta_items')->where('venta_id', $id)->get();
            $pagos = DB::table('detalles_pago')->where('venta_id', $id)->whereNull('deleted_at')->get();
            $plan = DB::table('planes_cuotas')->where('venta_id', $id)->first();
            $cuotas = $plan ? DB::table('cuotas')->where('plan_cuotas_id', $plan->id)->whereNull('deleted_at')->orderBy('numero_cuota')->get() : collect();

            $empresa = EmpresaSettings::get();

            $pdf = Pdf::loadView('pdfs.nota-venta', compact('venta', 'cliente', 'vehiculo', 'pagos', 'plan', 'cuotas', 'items', 'empresa'))
                ->setPaper('a4', 'portrait');

            // Autoprint Script (Optional)
            try {
                $pdf->render();
                $canvas = $pdf->getCanvas();
                if ($canvas) {
                    $canvas->get_cpdf()->openObject();
                    $canvas->get_cpdf()->addJavascript("print(true);");
                    $canvas->get_cpdf()->closeObject();
                }
            } catch (\Exception $e) {
                Log::warning('Error al renderizar JS de impresión: ' . $e->getMessage());
            }

            return $pdf->stream("nota_venta_{$venta->numero_venta}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de venta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF. Por favor, verifique los permisos de la carpeta storage.'], 500);
        }
    }

    /**
     * Generate cuotas from the manual grid.
     */
    private function generarCuotasManuales(int $planId, int $ventaId, string $moneda, array $cuotasManual): void
    {
        $cuotas = [];
        $i = 1;
        $totalCuotas = count($cuotasManual);

        foreach ($cuotasManual as $row) {
            $monto = floatval($row['monto'] ?? 0);
            if ($monto <= 0)
                continue;

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

    /**
     * Generate cuotas automatically (Francesa, Alemana, or Manual uniform).
     */
    private function generarCuotasAutomaticas(int $planId, int $ventaId, string $moneda, float $capital, Request $request): void
    {
        $n = $request->input('numero_cuotas', 12);
        $tasa = ($request->input('tasa_interes_mensual', 0)) / 100;
        $tipo = $request->input('tipo_plan', 'FRANCESA');
        $fecha = \Carbon\Carbon::parse($request->input('fecha_primera_cuota', now()->addMonth()->toDateString()));

        $refuerzoCada = intval($request->input('refuerzo_cada', 0));
        $refuerzoMonto = floatval($request->input('refuerzo_monto', 0));

        // If there are reinforcements, reduce the capital proportionally
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
                $cuotaTotal = $tasa > 0
                    ? $capital * $tasa / (1 - pow(1 + $tasa, -$n))
                    : $capital / $n;
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

            // Insert reinforcement cuota after every N months
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

        // Update total_cuotas with actual final count
        $totalFinal = count($cuotas);
        foreach ($cuotas as &$c) {
            $c['total_cuotas'] = $totalFinal;
        }

        DB::table('cuotas')->insert($cuotas);
        DB::table('planes_cuotas')->where('id', $planId)->update(['numero_cuotas' => $totalFinal]);
    }
}
