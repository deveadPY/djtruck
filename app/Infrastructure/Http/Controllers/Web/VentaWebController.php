<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Domain\Finance\Services\CajaService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Application\Sales\CreateSaleDTO;
use App\Application\Sales\UpdateSaleDTO;
use App\Application\Sales\CancelSaleDTO;
use App\Application\Sales\SaleApplicationService;
use App\Infrastructure\Http\Requests\StoreSaleRequest;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use App\Infrastructure\Settings\EmpresaSettings;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * VentaWebController — Clean Architecture
 *
 * Usa SaleApplicationService como fachada (que delega a UseCases).
 * Las queries directas se mantienen solo para:
 *   - index() con filtros complejos (búsqueda + paginación)
 *   - create()/edit() para llenar combos de selección
 * Show() usa repository con eager loading (sin N+1).
 */
class VentaWebController extends Controller
{
    public function __construct(
        private readonly CajaService $cajaService,
        private readonly SaleApplicationService $saleService,
        private readonly \App\Application\Sales\CreateSaleUseCase $createSaleUseCase,
        private readonly \App\Application\Sales\UpdateSaleUseCase $updateSaleUseCase,
        private readonly \App\Application\Sales\CancelSaleUseCase $cancelSaleUseCase
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
            $venta = $this->createSaleUseCase->execute(CreateSaleDTO::fromRequest($request));

            return redirect()->route('ventas.show', $venta->id)
                ->with('success', 'Venta y pagos registrados correctamente.')
                ->with('show_print_modal', true);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al registrar la venta: ' . $e->getMessage()]);
        }
    }

    public function show($id, \App\Application\Installments\InstallmentApplicationService $installmentService)
    {
        // Una sola consulta con eager loading: cliente, vehiculo, vendedor
        $venta = $this->saleService->findById((int) $id);
        if (!$venta || $venta->deleted_at) {
            abort(404, 'Venta no encontrada');
        }

        // Toda la lectura va por el ApplicationService (Repository pattern)
        $items      = $this->saleService->getItems((int) $id);
        $pagos      = $this->saleService->getPayments((int) $id);
        $plan       = $this->saleService->getInstallmentPlan((int) $id);
        $cuotas     = $plan ? $installmentService->getByPlan((int) $plan->id) : collect();
        $documentos = $this->saleService->getDocuments((int) $id);

        $rentabilidad = $this->saleService->calculateRentability($venta);

        return view('ventas.show', compact('venta', 'pagos', 'plan', 'cuotas', 'rentabilidad', 'documentos', 'items'));
    }

    public function edit($id)
    {
        $venta = DB::table('ventas')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $venta->items = DB::table('venta_items')->where('venta_id', $id)->whereNull('deleted_at')->get();
        $venta->pagos = DB::table('detalles_pago')->where('venta_id', $id)->whereNull('deleted_at')->get();
        $plan = DB::table('planes_cuotas')->where('venta_id', $id)->where('estado', '!=', 'CANCELADO')->first();
        $cuotas = $plan ? DB::table('cuotas')->where('plan_cuotas_id', $plan->id)->whereNull('deleted_at')->orderBy('numero_cuota')->get() : collect();

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

        return view('ventas.edit', compact('venta', 'vehiculos', 'clientes', 'cajas', 'vehiculos_canje', 'repuestos', 'plan', 'cuotas'));
    }

    public function update(StoreSaleRequest $request, $id)
    {
        try {
            $this->updateSaleUseCase->execute(UpdateSaleDTO::fromRequest((int)$id, $request));
            return redirect()->route('ventas.show', $id)
                ->with('success', 'Venta actualizada correctamente. Todos los movimientos han sido ajustados.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al actualizar la venta: ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $motivo = $request->input('motivo');
            $this->cancelSaleUseCase->execute(new CancelSaleDTO((int)$id, $motivo));
            return redirect()->route('ventas.index')
                ->with('success', 'Venta cancelada correctamente. Stock devuelto, cuotas canceladas y movimientos de caja anulados.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al cancelar la venta: ' . $e->getMessage()]);
        }
    }

    public function imprimirNotaVenta($id)
    {
        try {
            $venta = $this->saleService->findById((int) $id);
            if (!$venta) {
                abort(404, 'Venta no encontrada');
            }

            $items  = DB::table('venta_items')->where('venta_id', $id)->whereNull('deleted_at')->get();
            $pagos  = DB::table('detalles_pago')->where('venta_id', $id)->whereNull('deleted_at')->get();
            $plan   = DB::table('planes_cuotas')->where('venta_id', $id)->whereNull('deleted_at')->first();
            $cuotas = $plan
                ? DB::table('cuotas')->where('plan_cuotas_id', $plan->id)->whereNull('deleted_at')->orderBy('numero_cuota')->get()
                : collect();
            $empresa = EmpresaSettings::get();

            $pdf = Pdf::loadView('pdfs.nota-venta', [
                'venta'    => $venta,
                'cliente'  => $venta->cliente,    // ya cargado por eager loading
                'vehiculo' => $venta->vehiculo,   // ya cargado por eager loading
                'pagos'    => $pagos,
                'plan'     => $plan,
                'cuotas'   => $cuotas,
                'items'    => $items,
                'empresa'  => $empresa,
            ])->setPaper('a4', 'portrait')->setOption(['isPhpEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

            return $pdf->stream("nota_venta_{$venta->numero_venta}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de venta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF.'], 500);
        }
    }
}
