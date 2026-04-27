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

    public function export(Request $request, string $type): JsonResponse
    {
        return $this->errorResponse("Export de {$type} no implementado aún.", null, 501);
    }
}
