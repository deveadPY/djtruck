<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\Currency\LaMonedaRateSync;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * CotizacionWebController — Gestiona TASAS DE CAMBIO de monedas (PYG, BRL).
 *
 * Nota: la tabla subyacente fue renombrada a `tasas_cambio` para liberar el
 * nombre `cotizaciones` para el futuro módulo de presupuestos de venta.
 * Las rutas/URLs/route names se mantienen como `cotizaciones.*` por
 * compatibilidad con bookmarks y navegación existente.
 */
class CotizacionWebController extends Controller
{
    private const TABLE = 'tasas_cambio';

    public function index()
    {
        $cotizaciones = DB::table(self::TABLE)->orderBy('id', 'desc')->get();

        $latestPyg = DB::table(self::TABLE)->where('moneda_destino', 'PYG')->orderBy('id', 'desc')->first();
        $latestBrl = DB::table(self::TABLE)->where('moneda_destino', 'BRL')->orderBy('id', 'desc')->first();

        return view('cotizaciones.index', compact('cotizaciones', 'latestPyg', 'latestBrl'));
    }

    public function create()
    {
        return view('cotizaciones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha'          => 'nullable|date|before_or_equal:today',
            'moneda_destino' => 'required|in:PYG,BRL',
            'venta'          => 'required|numeric|min:0.0001|max:9999999999',
            'compra'         => 'nullable|numeric|min:0|max:9999999999',
        ]);

        $data['fecha']  = $data['fecha']  ?? date('Y-m-d');
        $data['compra'] = $data['compra'] ?? $data['venta'];

        DB::table(self::TABLE)->insert([
            'fecha'          => $data['fecha'],
            'moneda_destino' => $data['moneda_destino'],
            'compra'         => $data['compra'],
            'venta'          => $data['venta'],
            'created_by'     => Auth::id(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('cotizaciones.index')->with('success', 'Tasa de cambio guardada.');
    }

    /**
     * Endpoint para JS — tasas vigentes a la fecha pedida (hoy por defecto).
     * Para hoy, primero intenta actualizar desde La Moneda (como mucho cada 30 min).
     */
    public function getTodayRates(Request $request, LaMonedaRateSync $sync)
    {
        // Los formularios mandan la fecha sin cabecera JSON: una fecha inválida no debe redirigir.
        $hoy = LaMonedaRateSync::hoy();
        try {
            $fecha = min(Carbon::parse((string) $request->query('fecha', $hoy))->toDateString(), $hoy);
        } catch (\Throwable) {
            $fecha = $hoy;
        }

        if ($fecha === $hoy) {
            // Sin tasa de hoy se espera a La Moneda (máx. ~5 s); si ya hay, se refresca tras responder.
            $sync->tieneTasasDeHoy() ? defer(fn () => $sync->syncIfDue()) : $sync->syncIfDue();
        }

        $tasas = [];
        $detalle = [];
        foreach (['PYG', 'BRL'] as $moneda) {
            $fila = DB::table(self::TABLE)->where('moneda_destino', $moneda)
                ->whereDate('fecha', '<=', $fecha)->orderByDesc('fecha')->orderByDesc('id')->first()
                ?? DB::table(self::TABLE)->where('moneda_destino', $moneda)->orderBy('fecha')->orderBy('id')->first();

            // 1 se mantiene por compatibilidad con los formularios que solo leen PYG/BRL.
            $tasas[$moneda] = $fila ? (float) $fila->venta : 1;
            $detalle[$moneda] = $fila ? [
                'fecha'  => substr((string) $fila->fecha, 0, 10),
                'fuente' => (int) $fila->created_by === LaMonedaRateSync::SISTEMA ? 'La Moneda' : 'Carga manual',
            ] : null;
        }

        return response()->json($tasas + ['detalle' => $detalle]);
    }
}
