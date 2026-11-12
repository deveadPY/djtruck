<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\Currency\LaMonedaRateSync;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // El negocio opera en America/Asuncion aunque la app corra en UTC.
        $hoy     = LaMonedaRateSync::hoy();
        $manana  = Carbon::parse($hoy)->addDay()->toDateString();
        $en7dias = Carbon::parse($hoy)->addDays(7)->toDateString();

        // ── Stats de inventario ───────────────────────────────────────────
        $totalVehiculos = DB::table('vehiculos')->whereNull('deleted_at')->count();
        $disponibles    = DB::table('vehiculos')->where('estado', 'DISPONIBLE')->whereNull('deleted_at')->count();
        $enPreparacion  = DB::table('vehiculos')->where('estado', 'EN_PREPARACION')->whereNull('deleted_at')->count();
        $enStock        = DB::table('vehiculos')->whereNotIn('estado', ['VENDIDO', 'BAJA'])->whereNull('deleted_at')->count();

        // ── Ventas del mes ────────────────────────────────────────────────
        $ventasMes = DB::table('ventas')
            ->whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->whereIn('estado', ['COMPLETADO', 'EN_PROCESO'])
            ->whereNull('deleted_at')
            ->count();

        $ingresosUsdMes = (float) DB::table('ventas')
            ->whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->sum('precio_venta_usd');

        $margenUsdMes = (float) DB::table('ventas')
            ->whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->selectRaw('SUM(COALESCE(margen_bruto_usd, precio_venta_usd - valor_libro_snapshot)) as total')
            ->value('total');

        // ── Cuotas ───────────────────────────────────────────────────────
        // Mora: vencidas/en mora por estado o pendientes con fecha pasada (igual que la pestaña de notificaciones).
        $mora = DB::table('cuotas')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($hoy) {
                $q->whereIn('estado', ['VENCIDA', 'EN_MORA'])
                    ->orWhere(fn ($q2) => $q2->where('estado', 'PENDIENTE')->where('fecha_vencimiento', '<', $hoy));
            })
            ->selectRaw('COUNT(*) as cantidad, SUM(capital + interes + COALESCE(interes_mora, 0) - COALESCE(monto_pagado, 0)) as total')
            ->first();
        $cuotasEnMora = (int) $mora->cantidad;
        $montoMoraUsd = (float) $mora->total;

        $hoyAgg = DB::table('cuotas')
            ->where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', $hoy)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as cantidad, SUM(capital + interes - COALESCE(monto_pagado, 0)) as total')
            ->first();
        $cuotasHoy   = (int) $hoyAgg->cantidad;
        $montoHoyUsd = (float) $hoyAgg->total;

        // Lista corta solo si son pocas; si son más, se ve en notificaciones.
        $cuotasHoyLista = $cuotasHoy >= 1 && $cuotasHoy <= 5
            ? DB::table('cuotas')
                ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
                ->join('clientes', 'planes_cuotas.cliente_id', '=', 'clientes.id')
                ->where('cuotas.estado', 'PENDIENTE')
                ->where('cuotas.fecha_vencimiento', $hoy)
                ->whereNull('cuotas.deleted_at')
                ->select([
                    'cuotas.id',
                    'planes_cuotas.id as plan_id',
                    DB::raw('COALESCE(clientes.razon_social, clientes.nombre_fantasia) as cliente_nombre'),
                    DB::raw('cuotas.capital + cuotas.interes - COALESCE(cuotas.monto_pagado, 0) as monto'),
                ])
                ->orderBy('cuotas.id')
                ->get()
            : collect();

        // ── Stock bajo mínimo ─────────────────────────────────────────────
        $stockBajoMinimo = DB::table('stock_repuestos')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->whereRaw('stock_actual <= stock_minimo')
            ->count();

        $repuestosBajos = DB::table('stock_repuestos')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->whereRaw('stock_actual <= stock_minimo')
            ->select('id', 'codigo', 'descripcion', 'stock_actual', 'stock_minimo')
            ->limit(5)
            ->get();

        // ── Últimos vehículos ─────────────────────────────────────────────
        $vehiculos = DB::table('vehiculos')
            ->whereNull('deleted_at')
            ->latest()
            ->limit(5)
            ->get();

        // ── Ventas recientes ──────────────────────────────────────────────
        $ventasRecientes = DB::table('ventas')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->join('vehiculos', 'ventas.vehiculo_id', '=', 'vehiculos.id')
            ->whereNull('ventas.deleted_at')
            ->orderByDesc('ventas.created_at')
            ->limit(6)
            ->select([
                'ventas.id',
                'ventas.numero_venta',
                'ventas.fecha_venta',
                'ventas.estado',
                'ventas.precio_venta_usd',
                DB::raw('COALESCE(ventas.margen_bruto_usd, ventas.precio_venta_usd - ventas.valor_libro_snapshot) as margen_bruto_usd'),
                'clientes.razon_social as cliente_nombre',
                'vehiculos.marca',
                'vehiculos.modelo',
            ])
            ->get();

        // ── Comparativo mes anterior ──────────────────────────────────────
        $ingresosUsdMesAnterior = (float) DB::table('ventas')
            ->whereMonth('fecha_venta', now()->subMonth()->month)
            ->whereYear('fecha_venta', now()->subMonth()->year)
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->sum('precio_venta_usd');

        $ventasMesAnterior = DB::table('ventas')
            ->whereMonth('fecha_venta', now()->subMonth()->month)
            ->whereYear('fecha_venta', now()->subMonth()->year)
            ->whereIn('estado', ['COMPLETADO', 'EN_PROCESO'])
            ->whereNull('deleted_at')
            ->count();

        // ── Cobros próximos 7d (suma) ─────────────────────────────────────
        $cobrosProximos7dTotal = (float) DB::table('cuotas')
            ->where('estado', 'PENDIENTE')
            ->whereBetween('fecha_vencimiento', [$hoy, $en7dias])
            ->whereNull('deleted_at')
            ->selectRaw('SUM(capital + interes) as total')
            ->value('total');

        // ── Distribución vehículos por estado ────────────────────────────
        $vehiculosPorEstado = DB::table('vehiculos')
            ->whereNull('deleted_at')
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // ── Gráfico: ventas por mes (último año) ──────────────────────────
        $ventasPorMes = DB::table('ventas')
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->where('fecha_venta', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->selectRaw("DATE_FORMAT(fecha_venta, '%Y-%m') as mes, COUNT(*) as cantidad, SUM(precio_venta_usd) as total_usd, SUM(COALESCE(margen_bruto_usd, 0)) as margen_usd")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // ── Cuotas próximas 7 días ────────────────────────────────────────
        $cuotasProximas = DB::table('cuotas')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->where('cuotas.estado', 'PENDIENTE')
            ->whereBetween('cuotas.fecha_vencimiento', [$hoy, $en7dias])
            ->whereNull('cuotas.deleted_at')
            ->select([
                'cuotas.id',
                'cuotas.numero_cuota',
                'cuotas.fecha_vencimiento',
                DB::raw('cuotas.capital + cuotas.interes as total_cuota'),
                'clientes.razon_social as cliente_nombre',
                'ventas.numero_venta',
            ])
            ->orderBy('cuotas.fecha_vencimiento')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'enStock',
            'vehiculos',
            'totalVehiculos',
            'disponibles',
            'enPreparacion',
            'ventasMes',
            'ventasMesAnterior',
            'ingresosUsdMes',
            'ingresosUsdMesAnterior',
            'margenUsdMes',
            'cuotasEnMora',
            'montoMoraUsd',
            'cuotasHoy',
            'montoHoyUsd',
            'cuotasHoyLista',
            'hoy',
            'manana',
            'stockBajoMinimo',
            'repuestosBajos',
            'cobrosProximos7dTotal',
            'vehiculosPorEstado',
            'ventasRecientes',
            'ventasPorMes',
            'cuotasProximas',
        ));
    }
}
