<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportWebController extends Controller
{
    public function index(Request $request)
    {
        $desde = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        // ── Resumen de ventas ─────────────────────────────────────────────
        $resumenVentas = DB::table('ventas')
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->whereBetween('fecha_venta', [$desde, $hasta])
            ->selectRaw("
                COUNT(*) as total_ventas,
                SUM(precio_venta_usd) as total_usd,
                SUM(COALESCE(margen_bruto_usd, precio_venta_usd - valor_libro_snapshot)) as margen_total_usd,
                AVG(COALESCE(NULLIF(margen_pct, 0),
                    CASE WHEN valor_libro_snapshot > 0
                         THEN (precio_venta_usd - valor_libro_snapshot) / valor_libro_snapshot * 100
                         ELSE 0 END
                )) as margen_promedio_pct
            ")
            ->first();

        // ── Ventas por mes (gráfico) ──────────────────────────────────────
        $ventasPorMes = DB::table('ventas')
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->where('fecha_venta', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->selectRaw("DATE_FORMAT(fecha_venta, '%Y-%m') as mes, COUNT(*) as cantidad, SUM(precio_venta_usd) as total_usd, SUM(COALESCE(margen_bruto_usd, precio_venta_usd - valor_libro_snapshot)) as margen_usd")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // ── Rentabilidad por venta ─────────────────────────────────────────
        $rentabilidad = DB::table('ventas')
            ->join('vehiculos', 'ventas.vehiculo_id', '=', 'vehiculos.id')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->where('ventas.estado', 'COMPLETADO')
            ->whereNull('ventas.deleted_at')
            ->whereBetween('ventas.fecha_venta', [$desde, $hasta])
            ->select([
                'ventas.id',
                'ventas.numero_venta',
                'ventas.fecha_venta',
                'vehiculos.marca',
                'vehiculos.modelo',
                'clientes.razon_social as cliente',
                'ventas.precio_venta_usd',
                'ventas.valor_libro_snapshot',
                DB::raw('COALESCE(ventas.margen_bruto_usd, ventas.precio_venta_usd - ventas.valor_libro_snapshot) as margen_bruto_usd'),
                DB::raw('COALESCE(ventas.margen_pct,
                    CASE WHEN ventas.valor_libro_snapshot > 0
                         THEN ROUND((ventas.precio_venta_usd - ventas.valor_libro_snapshot) / ventas.valor_libro_snapshot * 100, 2)
                         ELSE 0 END) as margen_pct'),
            ])
            ->orderByDesc('ventas.fecha_venta')
            ->paginate(15)
            ->withQueryString();

        // ── Valuación de stock ────────────────────────────────────────────
        $stockValuacion = DB::table('vehiculos')
            ->whereNotIn('estado', ['VENDIDO', 'BAJA'])
            ->whereNull('deleted_at')
            ->selectRaw("
                estado,
                COUNT(*) as cantidad,
                SUM(costo_origen_usd + total_gastos_usd) as valor_libro_usd,
                SUM(precio_venta_sugerido_usd) as precio_sugerido_usd
            ")
            ->groupBy('estado')
            ->get();

        $totalStockUsd = $stockValuacion->sum('valor_libro_usd');

        // ── Cuotas en mora por cliente ────────────────────────────────────
        $cuotasMora = DB::table('cuotas')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->whereIn('cuotas.estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('cuotas.deleted_at')
            ->selectRaw("
                clientes.id as cliente_id,
                clientes.razon_social as cliente,
                COUNT(cuotas.id) as cuotas_vencidas,
                SUM(cuotas.capital + cuotas.interes - COALESCE(cuotas.monto_pagado,0)) as monto_vencido_usd,
                MAX(DATEDIFF(CURDATE(), cuotas.fecha_vencimiento)) as max_dias_mora
            ")
            ->groupBy('clientes.id', 'clientes.razon_social')
            ->orderByDesc('monto_vencido_usd')
            ->get();

        // ── Top clientes por monto comprado ───────────────────────────────
        $topClientes = DB::table('ventas')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->where('ventas.estado', 'COMPLETADO')
            ->whereNull('ventas.deleted_at')
            ->selectRaw("
                clientes.id,
                clientes.razon_social,
                COUNT(ventas.id) as total_ventas,
                SUM(ventas.precio_venta_usd) as total_comprado_usd
            ")
            ->groupBy('clientes.id', 'clientes.razon_social')
            ->orderByDesc('total_comprado_usd')
            ->limit(10)
            ->get();

        return view('reportes.index', compact(
            'desde',
            'hasta',
            'resumenVentas',
            'ventasPorMes',
            'rentabilidad',
            'stockValuacion',
            'totalStockUsd',
            'cuotasMora',
            'topClientes',
        ));
    }

    public function export(Request $request, string $tipo)
    {
        $desde = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $data = match($tipo) {
            'ventas'      => $this->exportDataVentas($desde, $hasta),
            'rentabilidad'=> $this->exportDataRentabilidad($desde, $hasta),
            'mora'        => $this->exportDataMora(),
            'stock'       => $this->exportDataStock(),
            default       => abort(404, "Tipo de reporte no válido: {$tipo}"),
        };

        return Excel::download(new ReportExport($data['rows'], $data['headers']), "reporte-{$tipo}-{$desde}-{$hasta}.xlsx");
    }

    // ── Helpers para exportación ──────────────────────────────────────────

    private function exportDataVentas(string $desde, string $hasta): array
    {
        $rows = DB::table('ventas')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->join('vehiculos', 'ventas.vehiculo_id', '=', 'vehiculos.id')
            ->where('ventas.estado', 'COMPLETADO')
            ->whereNull('ventas.deleted_at')
            ->whereBetween('ventas.fecha_venta', [$desde, $hasta])
            ->select([
                'ventas.numero_venta',
                'ventas.fecha_venta',
                'clientes.razon_social as cliente',
                'vehiculos.marca',
                'vehiculos.modelo',
                'ventas.precio_venta_usd',
                'ventas.valor_libro_snapshot',
                DB::raw('COALESCE(ventas.margen_bruto_usd, ventas.precio_venta_usd - ventas.valor_libro_snapshot) as margen_bruto_usd'),
                DB::raw('COALESCE(ventas.margen_pct, CASE WHEN ventas.valor_libro_snapshot > 0 THEN ROUND((ventas.precio_venta_usd - ventas.valor_libro_snapshot) / ventas.valor_libro_snapshot * 100, 2) ELSE 0 END) as margen_pct'),
            ])
            ->orderBy('ventas.fecha_venta')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return [
            'headers' => ['N° Venta', 'Fecha', 'Cliente', 'Marca', 'Modelo', 'Precio USD', 'Valor Libro USD', 'Margen USD', 'Margen %'],
            'rows'    => $rows,
        ];
    }

    private function exportDataRentabilidad(string $desde, string $hasta): array
    {
        return $this->exportDataVentas($desde, $hasta);
    }

    private function exportDataMora(): array
    {
        $rows = DB::table('cuotas')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->whereIn('cuotas.estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('cuotas.deleted_at')
            ->select([
                'clientes.razon_social as cliente',
                'ventas.numero_venta',
                'cuotas.numero_cuota',
                'cuotas.fecha_vencimiento',
                'cuotas.estado',
                DB::raw('cuotas.capital + cuotas.interes - COALESCE(cuotas.monto_pagado,0) as pendiente_usd'),
                DB::raw('DATEDIFF(CURDATE(), cuotas.fecha_vencimiento) as dias_mora'),
            ])
            ->orderBy('clientes.razon_social')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return [
            'headers' => ['Cliente', 'N° Venta', 'N° Cuota', 'Vencimiento', 'Estado', 'Pendiente USD', 'Días Mora'],
            'rows'    => $rows,
        ];
    }

    private function exportDataStock(): array
    {
        $rows = DB::table('vehiculos')
            ->whereNotIn('estado', ['VENDIDO', 'BAJA'])
            ->whereNull('deleted_at')
            ->select(['numero_chasis', 'marca', 'modelo', 'anio', 'estado', 'costo_origen_usd', 'total_gastos_usd', DB::raw('costo_origen_usd + total_gastos_usd as valor_libro_usd'), 'precio_venta_sugerido_usd'])
            ->orderBy('estado')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return [
            'headers' => ['Chasis', 'Marca', 'Modelo', 'Año', 'Estado', 'Costo USD', 'Gastos USD', 'Valor Libro USD', 'Precio Sugerido USD'],
            'rows'    => $rows,
        ];
    }
}
