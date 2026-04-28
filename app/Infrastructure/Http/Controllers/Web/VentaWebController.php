<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Http\Requests\StoreSaleRequest;
use App\Infrastructure\Services\ClienteCreditService;
use App\Infrastructure\Settings\EmpresaSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaWebController extends Controller
{
    public function __construct(
        private readonly CajaService          $cajaService,
        private readonly InstallmentGenerator $installmentGenerator,
        private readonly ClienteCreditService $creditService,
    ) {}

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
            ->when(filled($estado), fn($q) => $q->where('ventas.estado', $estado))
            ->when(filled($desde),  fn($q) => $q->where('ventas.fecha_venta', '>=', $desde))
            ->when(filled($hasta),  fn($q) => $q->where('ventas.fecha_venta', '<=', $hasta))
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

        $clientes = DB::table('clientes')
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($cliente) {
                $cliente->credito_disponible_usd = $this->creditService->creditoDisponibleUsd(
                    $cliente->id,
                    floatval($cliente->linea_credito_usd ?? 0)
                );
                return $cliente;
            });

        $cajas           = DB::table('cajas')->where('activo', true)->get();
        $vehiculos_canje = DB::table('vehiculos')
            ->whereIn('estado', ['DISPONIBLE', 'EN_PREPARACION', 'TOMA'])
            ->whereNull('deleted_at')
            ->get();
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

            $cliente      = DB::table('clientes')->where('id', $data['cliente_id'])->first();
            $lineaCredito = floatval($cliente->linea_credito_usd ?? 0);

            if ($lineaCredito > 0) {
                $creditoDisponible = $this->creditService->creditoDisponibleUsd(
                    $data['cliente_id'],
                    $lineaCredito
                );

                if ($capitalTotalUsdEstimado > $creditoDisponible) {
                    return back()->withInput()->withErrors([
                        'capital_total_usd' => sprintf(
                            'El capital a financiar (USD %.2f) supera la línea de crédito disponible del cliente (USD %.2f).',
                            $capitalTotalUsdEstimado,
                            $creditoDisponible
                        ),
                    ]);
                }
            }
        }

        // ── Métricas del carrito ──────────────────────────────────────────
        $tipoPlan        = $request->input('tipo_plan', 'MANUAL');
        $capitalTotalUsd = floatval($request->input('capital_total_usd', 0));
        unset($data['tipo_plan'], $data['capital_total_usd'], $data['numero_cuotas'], $data['fecha_primera_cuota'], $data['items']);

        $items = $request->input('items', []);
        $valorLibroTotal = array_sum(array_map(
            fn($item) => (float)($item['costo_snapshot_usd'] ?? 0) * (float)$item['cantidad'],
            $items
        ));

        $precioNeto               = max(0, (float)$data['precio_venta_usd'] - (float)($data['descuento_usd'] ?? 0));
        $data['valor_libro_snapshot'] = $valorLibroTotal;
        $data['margen_bruto_usd']     = round($precioNeto - $valorLibroTotal, 4);
        $data['margen_pct']           = $valorLibroTotal > 0
            ? round(($data['margen_bruto_usd'] / $valorLibroTotal) * 100, 4)
            : 0;

        $data['vendedor_id'] = Auth::id();
        $data['created_by']  = Auth::id();

        DB::beginTransaction();
        try {
            // ── Insertar venta — usar PK como secuencia única ─────────────
            $ventaId = DB::table('ventas')->insertGetId($data + [
                'numero_venta' => '',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $numeroVenta = 'V-' . date('Ym') . '-' . str_pad($ventaId, 4, '0', STR_PAD_LEFT);
            DB::table('ventas')->where('id', $ventaId)->update(['numero_venta' => $numeroVenta]);
            $data['numero_venta'] = $numeroVenta;

            // ── Ítems del carrito ─────────────────────────────────────────
            $tasaConversion = (float)($data['tasa_cambio_venta'] ?? 1);
            foreach ($items as $item) {
                $precioMoneda = (float)$item['precio_unitario_usd'] * $tasaConversion;

                DB::table('venta_items')->insert([
                    'venta_id'               => $ventaId,
                    'itemable_id'            => $item['itemable_id'],
                    'itemable_type'          => $item['itemable_type'],
                    'descripcion'            => $item['descripcion'] ?? 'Item sin descripción',
                    'cantidad'               => $item['cantidad'],
                    'precio_unitario_moneda' => $precioMoneda,
                    'precio_unitario_usd'    => $item['precio_unitario_usd'],
                    'subtotal_moneda'        => $precioMoneda * (float)$item['cantidad'],
                    'subtotal_usd'           => (float)$item['precio_unitario_usd'] * (float)$item['cantidad'],
                    'costo_snapshot_usd'     => $item['costo_snapshot_usd'] ?? 0,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                if ($item['itemable_type'] === 'App\\Models\\Vehicle') {
                    DB::table('vehiculos')->where('id', $item['itemable_id'])
                        ->update(['estado' => 'VENDIDO', 'updated_at' => now()]);
                }

                if ($item['itemable_type'] === 'App\\Models\\StockRepuesto') {
                    DB::table('stock_repuestos')
                        ->where('id', $item['itemable_id'])
                        ->decrement('stock_actual', $item['cantidad']);
                }
            }

            // ── Pagos iniciales ───────────────────────────────────────────
            $pagos                = $request->input('pagos', []);
            $totalMontoInicialUsd = 0;

            foreach ($pagos as $pago) {
                $montoUsd = floatval($pago['monto_usd'] ?? 0);
                if ($montoUsd <= 0) {
                    continue;
                }
                $totalMontoInicialUsd += $montoUsd;

                DB::table('detalles_pago')->insert([
                    'venta_id'           => $ventaId,
                    'tipo_pago'          => $pago['tipo'] ?? 'EFECTIVO',
                    'moneda'             => 'USD',
                    'monto_moneda'       => $montoUsd,
                    'monto_usd'          => $montoUsd,
                    'tasa_cambio'        => 1,
                    'vehiculo_canje_id'  => ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id']))
                        ? $pago['vehiculo_canje_id'] : null,
                    'referencia_bancaria' => $pago['referencia'] ?? null,
                    'fecha_pago'         => $data['fecha_venta'],
                    'observaciones'      => $modalidad === 'CUOTAS' ? 'Entrega inicial' : null,
                    'created_by'         => Auth::id(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                if ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id'])) {
                    DB::table('vehiculos')->where('id', $pago['vehiculo_canje_id'])
                        ->update(['estado' => 'TOMA', 'updated_at' => now()]);
                }

                $tipoPagoActual = $pago['tipo'] ?? 'EFECTIVO';
                if (in_array($tipoPagoActual, ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'TARJETA'])) {
                    $modalidadLabel = $modalidad === 'CUOTAS' ? 'entrega inicial' : 'contado';
                    $tipoLabel = match ($tipoPagoActual) {
                        'EFECTIVO'      => 'Efectivo',
                        'TRANSFERENCIA' => 'Transferencia',
                        'CHEQUE'        => 'Cheque',
                        'TARJETA'       => 'Tarjeta',
                        default         => $tipoPagoActual,
                    };
                    try {
                        $this->cajaService->ingresoCapital(
                            "Venta {$numeroVenta} – {$tipoLabel} ({$modalidadLabel})",
                            'USD', $montoUsd, $montoUsd, $ventaId, 'venta'
                        );
                    } catch (\RuntimeException $e) {
                        Log::warning('VentaWebController: no se pudo registrar en caja: ' . $e->getMessage());
                    }
                }
            }

            // ── Plan de cuotas ────────────────────────────────────────────
            if ($modalidad === 'CUOTAS') {
                if ($capitalTotalUsd <= 0) {
                    $capitalTotalUsd = max(0, $precioFinalUsd - $totalMontoInicialUsd);
                }

                $cuotasManual = $request->input('cuotas_manual', []);
                $numeroCuotas = (int) $request->input('numero_cuotas', 12);
                if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
                    $numeroCuotas = count($cuotasManual);
                }

                $planId = DB::table('planes_cuotas')->insertGetId([
                    'venta_id'              => $ventaId,
                    'cliente_id'            => $data['cliente_id'],
                    'tipo_plan'             => $tipoPlan,
                    'moneda'                => $data['moneda_venta'],
                    'capital_total'         => $capitalTotalUsd,
                    'capital_total_usd'     => $capitalTotalUsd,
                    'numero_cuotas'         => $numeroCuotas,
                    'tasa_interes_mensual'  => $request->input('tasa_interes_mensual', 0),
                    'fecha_primera_cuota'   => $request->input('fecha_primera_cuota', now()->addMonth()->toDateString()),
                    'estado'                => 'ACTIVO',
                    'created_by'            => Auth::id(),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
                    $this->installmentGenerator->generateManual(
                        $planId, $ventaId, $data['moneda_venta'], $cuotasManual
                    );
                } else {
                    $moneda  = Currency::from($data['moneda_venta']);
                    $capital = new Money($capitalTotalUsd, $moneda);
                    $this->installmentGenerator->generate(
                        $planId,
                        $ventaId,
                        InstallmentPlan::from($tipoPlan),
                        $capital,
                        $numeroCuotas,
                        (float) $request->input('tasa_interes_mensual', 0),
                        $request->input('fecha_primera_cuota', now()->addMonth()->toDateString()),
                        (int)   $request->input('refuerzo_cada', 0),
                        (float) $request->input('refuerzo_monto', 0),
                    );
                }

                DB::table('detalles_pago')->insert([
                    'venta_id'       => $ventaId,
                    'tipo_pago'      => 'PLAN_CUOTAS',
                    'moneda'         => $data['moneda_venta'],
                    'monto_moneda'   => $capitalTotalUsd,
                    'monto_usd'      => $capitalTotalUsd,
                    'tasa_cambio'    => 1,
                    'plan_cuotas_id' => $planId,
                    'fecha_pago'     => $data['fecha_venta'],
                    'created_by'     => Auth::id(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
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
        $venta         = DB::table('ventas')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $venta->vehiculo = DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first();
        $venta->cliente  = DB::table('clientes')->where('id', $venta->cliente_id)->first();
        $venta->vendedor = DB::table('users')->where('id', $venta->vendedor_id)->first();

        $items = DB::table('venta_items')->where('venta_id', $id)->get();
        $pagos = DB::table('detalles_pago')->where('venta_id', $id)->whereNull('deleted_at')->get();
        $plan  = DB::table('planes_cuotas')->where('venta_id', $id)->first();
        $cuotas = $plan
            ? DB::table('cuotas')->where('plan_cuotas_id', $plan->id)->whereNull('deleted_at')->orderBy('numero_cuota')->get()
            : collect();

        $precioFinalUsd = $venta->precio_venta_usd - ($venta->descuento_usd ?? 0);
        $rentabilidad   = $precioFinalUsd - $venta->valor_libro_snapshot;

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
            $venta           = DB::table('ventas')->where('id', $id)->firstOrFail();
            $venta->vehiculo = DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first();
            $venta->cliente  = DB::table('clientes')->where('id', $venta->cliente_id)->first();
            $venta->vendedor = DB::table('users')->where('id', $venta->vendedor_id)->first();

            $vehiculo = $venta->vehiculo;
            $cliente  = $venta->cliente;
            $items    = DB::table('venta_items')->where('venta_id', $id)->get();
            $pagos    = DB::table('detalles_pago')->where('venta_id', $id)->whereNull('deleted_at')->get();
            $plan     = DB::table('planes_cuotas')->where('venta_id', $id)->first();
            $cuotas   = $plan
                ? DB::table('cuotas')->where('plan_cuotas_id', $plan->id)->whereNull('deleted_at')->orderBy('numero_cuota')->get()
                : collect();

            $empresa = EmpresaSettings::get();

            $pdf = Pdf::loadView(
                'pdfs.nota-venta',
                compact('venta', 'cliente', 'vehiculo', 'pagos', 'plan', 'cuotas', 'items', 'empresa')
            )->setPaper('a4', 'portrait');

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
            return response()->json(['error' => 'Error al generar el PDF.'], 500);
        }
    }
}
