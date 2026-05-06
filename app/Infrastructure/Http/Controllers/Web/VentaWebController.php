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
    public function __construct(
        private readonly CajaService $cajaService,
        private readonly \App\Application\Sales\CreateSaleUseCase $createSaleUseCase
    ) {}

    public function index(Request $request)
    {
        // ... (el código de index se mantiene igual por ahora)
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
        try {
            $data = $request->validated();
            // Inyectamos los datos extras del request que no están en la validación base (como items y pagos)
            $data['items'] = $request->input('items', []);
            $data['pagos'] = $request->input('pagos', []);
            $data['cuotas_manual'] = $request->input('cuotas_manual', []);
            $data['tipo_plan'] = $request->input('tipo_plan', 'MANUAL');
            $data['capital_total_usd'] = $request->input('capital_total_usd', 0);
            $data['numero_cuotas'] = $request->input('numero_cuotas', 12);
            $data['fecha_primera_cuota'] = $request->input('fecha_primera_cuota');
            $data['tasa_interes_mensual'] = $request->input('tasa_interes_mensual', 0);
            $data['refuerzo_cada'] = $request->input('refuerzo_cada', 0);
            $data['refuerzo_monto'] = $request->input('refuerzo_monto', 0);

            $venta = $this->createSaleUseCase->execute($data);

            return redirect()->route('ventas.show', $venta->id)
                ->with('success', 'Venta y pagos registrados correctamente.')
                ->with('show_print_modal', true);
        } catch (\Exception $e) {
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
            $items = DB::table('venta_items')->where('venta_id', $id)->get();
            $pagos = DB::table('detalles_pago')->where('venta_id', $id)->whereNull('deleted_at')->get();
            $plan = DB::table('planes_cuotas')->where('venta_id', $id)->first();
            $cuotas = $plan ? DB::table('cuotas')->where('plan_cuotas_id', $plan->id)->whereNull('deleted_at')->orderBy('numero_cuota')->get() : collect();
            $empresa = EmpresaSettings::get();

            $pdf = Pdf::loadView('pdfs.nota-venta', [
                'venta' => $venta,
                'cliente' => DB::table('clientes')->where('id', $venta->cliente_id)->first(),
                'vehiculo' => DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first(),
                'pagos' => $pagos,
                'plan' => $plan,
                'cuotas' => $cuotas,
                'items' => $items,
                'empresa' => $empresa
            ])->setPaper('a4', 'portrait');

            return $pdf->stream("nota_venta_{$venta->numero_venta}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de venta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF.'], 500);
        }
    }
}
