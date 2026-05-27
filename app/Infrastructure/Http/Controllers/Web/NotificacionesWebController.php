<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificacionesWebController extends Controller
{
    /**
     * GET /api/notificaciones
     * Returns JSON payload — pre-filtered by server-side dismissals.
     */
    public function apiIndex(): JsonResponse
    {
        $userId  = Auth::id();
        $hoy     = Carbon::today()->toDateString();
        $en7dias = Carbon::today()->addDays(7)->toDateString();
        $mesInic = Carbon::today()->startOfMonth()->toDateString();
        $mesFin  = Carbon::today()->endOfMonth()->toDateString();

        // ── IDs descartados por este usuario ─────────────────────────────────
        $descartados = DB::table('notificaciones_descartadas')
            ->where('user_id', $userId)
            ->select('tipo', 'referencia_id')
            ->get()
            ->groupBy('tipo')
            ->map(fn($rows) => $rows->pluck('referencia_id')->toArray());

        $excluirCuotasMora  = $descartados->get('mora',     []);
        $excluirCuotasHoy   = $descartados->get('hoy',      []);
        $excluirCuotasProx  = $descartados->get('prox',     []);
        $excluirPagar       = $descartados->get('pagar',    []);
        $excluirDeclarar    = $descartados->get('declarar', []);
        $excluirStock       = $descartados->get('stock',    []);

        $baseCuotas = DB::table('cuotas as c')
            ->join('planes_cuotas as p', 'c.plan_cuotas_id', '=', 'p.id')
            ->join('ventas as v', 'c.venta_id', '=', 'v.id')
            ->join('clientes as cl', 'p.cliente_id', '=', 'cl.id')
            ->whereNull('c.deleted_at')
            ->select([
                'c.id as cuota_id', 'c.numero_cuota', 'c.total_cuotas',
                'c.capital', 'c.interes', 'c.moneda', 'c.fecha_vencimiento',
                'p.id as plan_id', 'v.numero_venta',
                DB::raw('COALESCE(cl.razon_social, cl.nombre_fantasia) as cliente_nombre'),
            ]);

        $cuotasHoy = (clone $baseCuotas)
            ->where('c.estado', 'PENDIENTE')
            ->where('c.fecha_vencimiento', '=', $hoy)
            ->when($excluirCuotasHoy, fn($q) => $q->whereNotIn('c.id', $excluirCuotasHoy))
            ->orderBy('c.fecha_vencimiento')->limit(20)->get();

        $cuotasProximas = (clone $baseCuotas)
            ->where('c.estado', 'PENDIENTE')
            ->whereBetween('c.fecha_vencimiento', [Carbon::today()->addDay()->toDateString(), $en7dias])
            ->when($excluirCuotasProx, fn($q) => $q->whereNotIn('c.id', $excluirCuotasProx))
            ->orderBy('c.fecha_vencimiento')->limit(20)->get();

        $cuotasMora = (clone $baseCuotas)
            ->where(function ($q) use ($hoy) {
                $q->where('c.estado', 'EN_MORA')
                    ->orWhere(fn($q2) => $q2->where('c.estado', 'PENDIENTE')->where('c.fecha_vencimiento', '<', $hoy));
            })
            ->when($excluirCuotasMora, fn($q) => $q->whereNotIn('c.id', $excluirCuotasMora))
            ->orderBy('c.fecha_vencimiento')->limit(20)->get();

        $facturasPagar = DB::table('facturas_proveedores as f')
            ->join('proveedores as p', 'f.proveedor_id', '=', 'p.id')
            ->whereIn('f.estado', ['PENDIENTE', 'APROBADA'])
            ->whereNull('f.deleted_at')
            ->when($excluirPagar, fn($q) => $q->whereNotIn('f.id', $excluirPagar))
            ->select([
                'f.id as factura_id', 'f.numero_factura', 'f.fecha_factura',
                'f.total_usd', 'f.moneda',
                DB::raw('COALESCE(p.razon_social, p.nombre_fantasia) as proveedor_nombre'),
            ])
            ->orderBy('f.fecha_factura')->limit(20)->get();

        $facturasDeclarar = DB::table('facturas_proveedores as f')
            ->join('proveedores as p', 'f.proveedor_id', '=', 'p.id')
            ->whereBetween('f.fecha_factura', [$mesInic, $mesFin])
            ->whereNull('f.deleted_at')
            ->when($excluirDeclarar, fn($q) => $q->whereNotIn('f.id', $excluirDeclarar))
            ->select([
                'f.id as factura_id', 'f.numero_factura', 'f.fecha_factura',
                'f.total_usd', 'f.moneda',
                DB::raw('COALESCE(p.razon_social, p.nombre_fantasia) as proveedor_nombre'),
            ])
            ->orderBy('f.fecha_factura', 'desc')->limit(20)->get();

        $repuestosBajos = DB::table('stock_repuestos')
            ->whereNull('deleted_at')->where('activo', true)
            ->where('stock_minimo', '>', 0)->whereRaw('stock_actual <= stock_minimo')
            ->when($excluirStock, fn($q) => $q->whereNotIn('id', $excluirStock))
            ->select(['id as repuesto_id', 'codigo', 'descripcion', 'stock_actual', 'stock_minimo'])
            ->limit(20)->get();

        $total = $cuotasHoy->count() + $cuotasProximas->count() + $cuotasMora->count()
               + $facturasPagar->count() + $facturasDeclarar->count() + $repuestosBajos->count();

        return response()->json([
            'total'              => $total,
            'cuotas_hoy'         => $cuotasHoy,
            'cuotas_proximas'    => $cuotasProximas,
            'cuotas_mora'        => $cuotasMora,
            'facturas_pagar'     => $facturasPagar,
            'facturas_declarar'  => $facturasDeclarar,
            'repuestos_bajos'    => $repuestosBajos,
        ]);
    }

    /**
     * POST /api/notificaciones/descartar
     * Persiste un descarte individual en BD.
     */
    public function descartar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo'          => 'required|string|in:mora,hoy,prox,pagar,declarar,stock',
            'referencia_id' => 'required|integer|min:1',
        ]);

        DB::table('notificaciones_descartadas')->updateOrInsert(
            ['user_id' => Auth::id(), 'tipo' => $data['tipo'], 'referencia_id' => $data['referencia_id']],
            ['descartado_at' => now()]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/notificaciones/descartar-todas
     * Persiste todos los ítems del payload actual como descartados.
     */
    public function descartarTodas(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'                => 'required|array',
            'items.*.tipo'         => 'required|string|in:mora,hoy,prox,pagar,declarar,stock',
            'items.*.referencia_id'=> 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $now    = now();

        $rows = array_map(fn($item) => [
            'user_id'       => $userId,
            'tipo'          => $item['tipo'],
            'referencia_id' => $item['referencia_id'],
            'descartado_at' => $now,
        ], $data['items']);

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('notificaciones_descartadas')->upsert(
                $chunk,
                ['user_id', 'tipo', 'referencia_id'],
                ['descartado_at']
            );
        }

        return response()->json(['ok' => true, 'descartadas' => count($rows)]);
    }

    /**
     * GET /notificaciones
     * Full-page notifications center.
     */
    public function index(): View
    {
        $hoy = Carbon::today()->toDateString();
        $en7dias = Carbon::today()->addDays(7)->toDateString();
        $mesInic = Carbon::today()->startOfMonth()->toDateString();
        $mesFin = Carbon::today()->endOfMonth()->toDateString();

        $baseCuotas = DB::table('cuotas as c')
            ->join('planes_cuotas as p', 'c.plan_cuotas_id', '=', 'p.id')
            ->join('ventas as v', 'c.venta_id', '=', 'v.id')
            ->join('clientes as cl', 'p.cliente_id', '=', 'cl.id')
            ->whereNull('c.deleted_at')
            ->select([
                'c.id as cuota_id',
                'c.numero_cuota',
                'c.total_cuotas',
                'c.capital',
                'c.interes',
                'c.interes_mora',
                'c.moneda',
                'c.fecha_vencimiento',
                'c.estado',
                'p.id as plan_id',
                'v.numero_venta',
                'v.id as venta_id',
                DB::raw('COALESCE(cl.razon_social, cl.nombre_fantasia) as cliente_nombre'),
                'cl.email as cliente_email',
            ]);

        $cuotasHoy = (clone $baseCuotas)->where('c.estado', 'PENDIENTE')
            ->where('c.fecha_vencimiento', '=', $hoy)
            ->orderBy('c.fecha_vencimiento')->paginate(50, pageName: 'hoy_page');

        $cuotasProximas = (clone $baseCuotas)->where('c.estado', 'PENDIENTE')
            ->whereBetween('c.fecha_vencimiento', [Carbon::today()->addDay()->toDateString(), $en7dias])
            ->orderBy('c.fecha_vencimiento')->paginate(50, pageName: 'prox_page');

        $cuotasMora = (clone $baseCuotas)
            ->where(function ($q) use ($hoy) {
                $q->where('c.estado', 'EN_MORA')
                    ->orWhere(function ($q2) use ($hoy) {
                        $q2->where('c.estado', 'PENDIENTE')->where('c.fecha_vencimiento', '<', $hoy);
                    });
            })
            ->orderBy('c.fecha_vencimiento')->paginate(50, pageName: 'mora_page');

        $facturasPagar = DB::table('facturas_proveedores as f')
            ->join('proveedores as p', 'f.proveedor_id', '=', 'p.id')
            ->whereIn('f.estado', ['PENDIENTE', 'APROBADA'])
            ->whereNull('f.deleted_at')
            ->select([
                'f.id as factura_id',
                'f.numero_factura',
                'f.fecha_factura',
                'f.total_usd',
                'f.moneda',
                DB::raw('COALESCE(p.razon_social, p.nombre_fantasia) as proveedor_nombre'),
            ])
            ->orderBy('f.fecha_factura', 'asc')
            ->paginate(50, pageName: 'pagar_page');

        $facturasDeclarar = DB::table('facturas_proveedores as f')
            ->join('proveedores as p', 'f.proveedor_id', '=', 'p.id')
            ->whereBetween('f.fecha_factura', [$mesInic, $mesFin])
            ->whereNull('f.deleted_at')
            ->select([
                'f.id as factura_id',
                'f.numero_factura',
                'f.fecha_factura',
                'f.total_usd',
                'f.moneda',
                DB::raw('COALESCE(p.razon_social, p.nombre_fantasia) as proveedor_nombre'),
            ])
            ->orderBy('f.fecha_factura', 'desc')
            ->paginate(50, pageName: 'declarar_page');

        // ── Stock Bajo Mínimo ─────────────────────────────────────────────
        $repuestosBajos = DB::table('stock_repuestos')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->whereRaw('stock_actual <= stock_minimo')
            ->select(['id', 'codigo', 'descripcion', 'stock_actual', 'stock_minimo'])
            ->paginate(50, pageName: 'stock_page');

        return view('notificaciones.index', compact(
            'cuotasHoy',
            'cuotasProximas',
            'cuotasMora',
            'facturasPagar',
            'facturasDeclarar',
            'repuestosBajos'
        ));
    }
}
