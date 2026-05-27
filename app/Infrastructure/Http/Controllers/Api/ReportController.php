<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportController extends BaseApiController
{
    public function __construct(private readonly CurrencyConverter $currency) {}

    public function salesSummary(Request $request): JsonResponse
    {
        $desde = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $resumen = DB::table('ventas')
            ->whereBetween('fecha_venta', [$desde, $hasta])
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_ventas,
                SUM(precio_venta_usd) as total_ventas_usd,
                SUM(precio_venta_usd - valor_libro_snapshot) as margen_total_usd,
                AVG((precio_venta_usd - valor_libro_snapshot) / NULLIF(valor_libro_snapshot, 0) * 100) as margen_promedio_pct
            ")
            ->first();

        $porMes = DB::table('ventas')
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->selectRaw("DATE_FORMAT(fecha_venta, '%Y-%m') as mes, COUNT(*) as ventas, SUM(precio_venta_usd) as total_usd")
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get();

        return $this->successResponse([
            'periodo'  => compact('desde', 'hasta'),
            'resumen'  => $resumen,
            'por_mes'  => $porMes,
        ]);
    }

    public function profitability(Request $request): JsonResponse
    {
        $ventas = DB::table('ventas')
            ->join('vehiculos', 'ventas.vehiculo_id', '=', 'vehiculos.id')
            ->where('ventas.estado', 'COMPLETADO')
            ->whereNull('ventas.deleted_at')
            ->selectRaw("
                ventas.id,
                ventas.numero_venta,
                ventas.fecha_venta,
                vehiculos.marca,
                vehiculos.modelo,
                ventas.precio_venta_usd,
                ventas.valor_libro_snapshot,
                (ventas.precio_venta_usd - ventas.valor_libro_snapshot) as margen_usd,
                ROUND((ventas.precio_venta_usd - ventas.valor_libro_snapshot) / NULLIF(ventas.valor_libro_snapshot, 0) * 100, 2) as margen_pct
            ")
            ->orderByDesc('ventas.fecha_venta')
            ->paginate(20);

        return $this->paginatedResponse($ventas);
    }

    public function stockValuation(): JsonResponse
    {
        $stock = DB::table('vehiculos')
            ->whereNotIn('estado', ['VENDIDO', 'BAJA'])
            ->whereNull('deleted_at')
            ->selectRaw("
                estado,
                COUNT(*) as cantidad,
                SUM(costo_origen_usd) as costo_total_usd,
                SUM(total_gastos_usd) as gastos_total_usd,
                SUM(costo_origen_usd + total_gastos_usd) as valor_libro_total_usd,
                SUM(precio_venta_sugerido_usd) as precio_sugerido_total_usd
            ")
            ->groupBy('estado')
            ->get();

        $totales = DB::table('vehiculos')
            ->whereNotIn('estado', ['VENDIDO', 'BAJA'])
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_unidades,
                SUM(costo_origen_usd + total_gastos_usd) as valor_libro_total_usd
            ")
            ->first();

        return $this->successResponse([
            'por_estado' => $stock,
            'totales'    => $totales,
            'en_pyg'     => $this->currency->format(
                $this->currency->fromBaseCurrency((float)$totales->valor_libro_total_usd, Currency::PYG)->amount,
                Currency::PYG
            ),
        ]);
    }

    public function overdueInstallments(): JsonResponse
    {
        $mora = DB::table('cuotas')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->whereIn('cuotas.estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('cuotas.deleted_at')
            ->selectRaw("
                clientes.id as cliente_id,
                clientes.razon_social as cliente,
                COUNT(cuotas.id) as cuotas_vencidas,
                SUM(cuotas.capital + cuotas.interes) as monto_vencido_usd,
                MAX(DATEDIFF(CURDATE(), cuotas.fecha_vencimiento)) as max_dias_mora
            ")
            ->groupBy('clientes.id', 'clientes.razon_social')
            ->orderByDesc('monto_vencido_usd')
            ->get();

        return $this->successResponse([
            'clientes_en_mora' => $mora,
            'total_mora_usd'   => $mora->sum('monto_vencido_usd'),
        ]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $desde = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $flujo = DB::table('movimientos_caja')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->whereNull('deleted_at')
            ->selectRaw("
                DATE(created_at) as fecha,
                SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END) as ingresos_usd,
                SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END) as egresos_usd
            ")
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return $this->successResponse(['flujo' => $flujo, 'periodo' => compact('desde', 'hasta')]);
    }

    /**
     * Proyección de cobranza: cuotas pendientes agrupadas por mes futuro.
     */
    public function collectionsForecast(Request $request): JsonResponse
    {
        $meses = (int) $request->get('meses', 12);
        $meses = max(1, min(36, $meses));

        $proyeccion = DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->whereNull('cuotas.deleted_at')
            ->whereBetween('cuotas.fecha_vencimiento', [
                now()->toDateString(),
                now()->addMonths($meses)->toDateString()
            ])
            ->selectRaw("
                DATE_FORMAT(cuotas.fecha_vencimiento, '%Y-%m') as mes,
                COUNT(cuotas.id) as cantidad_cuotas,
                SUM(cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado) as monto_a_cobrar_usd
            ")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $totalEsperado = $proyeccion->sum('monto_a_cobrar_usd');

        return $this->successResponse([
            'proyeccion'      => $proyeccion,
            'total_esperado'  => round((float) $totalEsperado, 2),
            'meses_proyectados' => $meses,
        ]);
    }

    /**
     * Antigüedad del inventario: cuántos días lleva cada vehículo en stock.
     */
    public function inventoryAge(): JsonResponse
    {
        $vehiculos = DB::table('vehiculos')
            ->whereNotIn('estado', ['VENDIDO', 'BAJA'])
            ->whereNull('deleted_at')
            ->selectRaw("
                id, numero_chasis, marca, modelo, anio, estado,
                costo_origen_usd, total_gastos_usd,
                (costo_origen_usd + total_gastos_usd) as valor_libro_usd,
                precio_venta_sugerido_usd,
                DATEDIFF(CURDATE(), created_at) as dias_en_stock,
                created_at
            ")
            ->orderByDesc('dias_en_stock')
            ->get();

        $resumen = [
            'menos_de_30_dias'  => $vehiculos->where('dias_en_stock', '<', 30)->count(),
            'de_30_a_90_dias'   => $vehiculos->whereBetween('dias_en_stock', [30, 89])->count(),
            'de_90_a_180_dias'  => $vehiculos->whereBetween('dias_en_stock', [90, 179])->count(),
            'mas_de_180_dias'   => $vehiculos->where('dias_en_stock', '>=', 180)->count(),
        ];

        return $this->successResponse([
            'vehiculos'             => $vehiculos,
            'resumen_antiguedad'    => $resumen,
            'dias_promedio_stock'   => round((float) $vehiculos->avg('dias_en_stock'), 1),
            'total_unidades'        => $vehiculos->count(),
        ]);
    }

    /**
     * Performance de ventas por vendedor.
     */
    public function salesByVendor(Request $request): JsonResponse
    {
        $desde = $request->get('desde', now()->startOfYear()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $vendedores = DB::table('ventas')
            ->leftJoin('users', 'ventas.vendedor_id', '=', 'users.id')
            ->whereBetween('ventas.fecha_venta', [$desde, $hasta])
            ->where('ventas.estado', 'COMPLETADO')
            ->whereNull('ventas.deleted_at')
            ->selectRaw("
                COALESCE(users.id, 0) as vendedor_id,
                COALESCE(users.name, 'Sin vendedor') as vendedor_nombre,
                COUNT(ventas.id) as ventas_completadas,
                SUM(ventas.precio_venta_usd) as total_ventas_usd,
                SUM(ventas.margen_bruto_usd) as margen_total_usd,
                AVG(ventas.margen_pct) as margen_promedio_pct
            ")
            ->groupBy('vendedor_id', 'vendedor_nombre')
            ->orderByDesc('total_ventas_usd')
            ->get();

        return $this->successResponse([
            'periodo'    => compact('desde', 'hasta'),
            'vendedores' => $vendedores,
            'totales'    => [
                'ventas'        => $vendedores->sum('ventas_completadas'),
                'total_usd'     => round((float) $vendedores->sum('total_ventas_usd'), 2),
                'margen_total'  => round((float) $vendedores->sum('margen_total_usd'), 2),
            ],
        ]);
    }

    /**
     * KPIs principales para el dashboard ejecutivo.
     */
    public function dashboardKpis(): JsonResponse
    {
        $inicioMes = now()->startOfMonth()->toDateString();
        $hoy       = now()->toDateString();

        $ventasMes = DB::table('ventas')
            ->whereBetween('fecha_venta', [$inicioMes, $hoy])
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as cantidad, COALESCE(SUM(precio_venta_usd), 0) as monto_usd, COALESCE(SUM(margen_bruto_usd), 0) as margen_usd")
            ->first();

        $stockDisponible = DB::table('vehiculos')
            ->where('estado', 'DISPONIBLE')
            ->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as cantidad, COALESCE(SUM(costo_origen_usd + total_gastos_usd), 0) as valor_libro_usd")
            ->first();

        $cuotasVencidas = DB::table('cuotas')
            ->whereIn('estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as cantidad, COALESCE(SUM(capital + interes + interes_mora - monto_pagado), 0) as monto_usd")
            ->first();

        $cobranzaMes = DB::table('cuotas')
            ->whereBetween('fecha_pago_efectivo', [$inicioMes, $hoy])
            ->where('estado', 'PAGADA')
            ->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as cantidad, COALESCE(SUM(monto_pagado), 0) as monto_usd")
            ->first();

        $cajaBalance = DB::table('movimientos_caja')
            ->whereNull('deleted_at')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END), 0) as saldo_usd
            ")
            ->first();

        $clientesEnMora = DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->whereIn('cuotas.estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('cuotas.deleted_at')
            ->distinct('planes_cuotas.cliente_id')
            ->count('planes_cuotas.cliente_id');

        $margenPctMes = ($ventasMes->monto_usd > 0)
            ? round((float) $ventasMes->margen_usd / (float) $ventasMes->monto_usd * 100, 2)
            : 0;

        return $this->successResponse([
            'periodo' => ['desde' => $inicioMes, 'hasta' => $hoy],
            'ventas_mes' => [
                'cantidad'      => (int) $ventasMes->cantidad,
                'monto_usd'     => round((float) $ventasMes->monto_usd, 2),
                'margen_usd'    => round((float) $ventasMes->margen_usd, 2),
                'margen_pct'    => $margenPctMes,
            ],
            'stock_disponible' => [
                'cantidad'         => (int) $stockDisponible->cantidad,
                'valor_libro_usd'  => round((float) $stockDisponible->valor_libro_usd, 2),
            ],
            'cuotas_vencidas' => [
                'cantidad'  => (int) $cuotasVencidas->cantidad,
                'monto_usd' => round((float) $cuotasVencidas->monto_usd, 2),
            ],
            'cobranza_mes' => [
                'cantidad'  => (int) $cobranzaMes->cantidad,
                'monto_usd' => round((float) $cobranzaMes->monto_usd, 2),
            ],
            'caja' => [
                'saldo_usd' => round((float) $cajaBalance->saldo_usd, 2),
            ],
            'clientes_en_mora' => $clientesEnMora,
        ]);
    }

    public function export(Request $request, string $type): JsonResponse
    {
        $allowed = ['sales-summary', 'profitability', 'stock-valuation', 'overdue-installments', 'cash-flow'];
        $format = strtolower($request->get('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            return $this->errorResponse("Formato inválido. Use xlsx, csv o pdf.", null, 400);
        }

        $desde = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());
        $timestamp = now()->format('Ymd_His');

        $export = match ($type) {
            'sales-summary'        => new \App\Exports\SalesSummaryExport($desde, $hasta),
            'inventory'            => new \App\Exports\InventoryExport(),
            'overdue-installments' => new \App\Exports\OverdueInstallmentsExport(),
            default                => null,
        };

        if (!$export) {
            return $this->errorResponse(
                "Tipo de reporte inválido. Disponibles: sales-summary, inventory, overdue-installments",
                null,
                400
            );
        }

        $filename = "{$type}_{$timestamp}.{$format}";

        $writerType = match ($format) {
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            'csv'  => \Maatwebsite\Excel\Excel::CSV,
            'pdf'  => \Maatwebsite\Excel\Excel::DOMPDF,
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename, $writerType);
    }
}
