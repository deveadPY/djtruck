<?php

namespace App\Infrastructure\Http\Controllers\Web;

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
     * Endpoint API para JS — devuelve las tasas vigentes del día.
     */
    public function getTodayRates(Request $request)
    {
        $pyg = DB::table(self::TABLE)->where('moneda_destino', 'PYG')->orderBy('id', 'desc')->first();
        $brl = DB::table(self::TABLE)->where('moneda_destino', 'BRL')->orderBy('id', 'desc')->first();

        return response()->json([
            'PYG' => $pyg ? (float) $pyg->venta : 1,
            'BRL' => $brl ? (float) $brl->venta : 1,
        ]);
    }
}
