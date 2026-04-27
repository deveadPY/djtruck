<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificacionesWebController extends Controller
{
    /**
     * GET /api/notificaciones
     * Returns JSON payload for the notification bell dropdown.
     */
    public function apiIndex(): JsonResponse
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
                'c.moneda',
                'c.fecha_vencimiento',
                'p.id as plan_id',
                'v.numero_venta',
                DB::raw('COALESCE(cl.razon_social, cl.nombre_fantasia) as cliente_nombre'),
            ]);

        // ── Cuotas a cobrar en el dia (hoy) ──────────────────────────────────
        $cuotasHoy = (clone $baseCuotas)->where('c.estado', 'PENDIENTE')
            ->where('c.fecha_vencimiento', '=', $hoy)
            ->orderBy('c.fecha_vencimiento')->limit(20)->get();

        // ── Cuotas apunto de cobrar (proximas 7 dias) ────────────────────────
        $cuotasProximas = (clone $baseCuotas)->where('c.estado', 'PENDIENTE')
            ->whereBetween('c.fecha_vencimiento', [Carbon::today()->addDay()->toDateString(), $en7dias])
            ->orderBy('c.fecha_vencimiento')->limit(20)->get();

        // ── Cuotas en mora (encima de la fecha) ──────────────────────────────
        $cuotasMora = (clone $baseCuotas)
            ->where(function ($q) use ($hoy) {
                $q->where('c.estado', 'EN_MORA')
                    ->orWhere(function ($q2) use ($hoy) {
                        $q2->where('c.estado', 'PENDIENTE')->where('c.fecha_vencimiento', '<', $hoy);
                    });
            })
            ->orderBy('c.fecha_vencimiento')->limit(20)->get();

        // ── Facturas a Pagar ─────────────────────────────────────────────────
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
            ->limit(20)->get();

        // ── Facturas a Declarar (mes actual) ─────────────────────────────────
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
            ->limit(20)->get();

        // ── Stock Bajo Mínimo ─────────────────────────────────────────────
        $repuestosBajos = DB::table('stock_repuestos')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->whereRaw('stock_actual <= stock_minimo')
            ->select(['id as repuesto_id', 'codigo', 'descripcion', 'stock_actual', 'stock_minimo'])
            ->limit(20)->get();

        $total = $cuotasHoy->count() + $cuotasProximas->count() + $cuotasMora->count() + $facturasPagar->count() + $facturasDeclarar->count() + $repuestosBajos->count();

        return response()->json([
            'total' => $total,
            'cuotas_hoy' => $cuotasHoy,
            'cuotas_proximas' => $cuotasProximas,
            'cuotas_mora' => $cuotasMora,
            'facturas_pagar' => $facturasPagar,
            'facturas_declarar' => $facturasDeclarar,
            'repuestos_bajos' => $repuestosBajos,
        ]);
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
