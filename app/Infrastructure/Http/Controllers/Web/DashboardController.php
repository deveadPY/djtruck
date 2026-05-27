<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = now()->toDateString();

        // ── Stats de inventario ───────────────────────────────────────────
        $totalVehiculos = DB::table('vehiculos')->whereNull('deleted_at')->count();
        $disponibles    = DB::table('vehiculos')->where('estado', 'DISPONIBLE')->whereNull('deleted_at')->count();
        $enPreparacion  = DB::table('vehiculos')->where('estado', 'EN_PREPARACION')->whereNull('deleted_at')->count();

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
        $cuotasEnMora = DB::table('cuotas')
            ->whereIn('estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('deleted_at')
            ->count();

        $montoMoraUsd = (float) DB::table('cuotas')
            ->whereIn('estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('deleted_at')
            ->selectRaw('SUM(capital + interes - COALESCE(monto_pagado, 0)) as total')
            ->value('total');

        $cuotasHoy = DB::table('cuotas')
            ->where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', $hoy)
            ->whereNull('deleted_at')
            ->count();

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
            ->whereBetween('fecha_vencimiento', [$hoy, now()->addDays(7)->toDateString()])
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
            ->whereBetween('cuotas.fecha_vencimiento', [$hoy, now()->addDays(7)->toDateString()])
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
