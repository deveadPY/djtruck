<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CotizacionWebController extends Controller
{
    public function index()
    {
        $cotizaciones = DB::table('cotizaciones')->orderBy('id', 'desc')->get();
        
        $latestPyg = DB::table('cotizaciones')->where('moneda_destino', 'PYG')->orderBy('id', 'desc')->first();
        $latestBrl = DB::table('cotizaciones')->where('moneda_destino', 'BRL')->orderBy('id', 'desc')->first();

        return view('cotizaciones.index', compact('cotizaciones', 'latestPyg', 'latestBrl'));
    }

    public function create()
    {
        return view('cotizaciones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => 'nullable|date',
            'moneda_destino' => 'required|in:PYG,BRL',
            'venta' => 'required|numeric|min:0',
            'compra' => 'nullable|numeric|min:0',
        ]);

        $data['fecha'] = $data['fecha'] ?? date('Y-m-d');
        $data['compra'] = $data['compra'] ?? $data['venta'];

        DB::table('cotizaciones')->insert([
            'fecha' => $data['fecha'],
            'moneda_destino' => $data['moneda_destino'],
            'compra' => $data['compra'],
            'venta' => $data['venta'],
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $msg = 'Cotización guardada.';

        return redirect()->route('cotizaciones.index')->with('success', $msg);
    }

    // Endpoint API para JS
    public function getTodayRates(Request $request)
    {
        $fecha = $request->query('fecha', date('Y-m-d'));

        // PYG - La más reciente
        $pyg = DB::table('cotizaciones')
            ->where('moneda_destino', 'PYG')
            ->orderBy('id', 'desc')
            ->first();

        // BRL - La más reciente
        $brl = DB::table('cotizaciones')
            ->where('moneda_destino', 'BRL')
            ->orderBy('id', 'desc')
            ->first();

        return response()->json([
            'PYG' => $pyg ? (float)$pyg->venta : 1,
            'BRL' => $brl ? (float)$brl->venta : 1,
        ]);
    }
}
