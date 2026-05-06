<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SaleWebController extends Controller
{
    public function index()
    {
        $ventas = DB::table('ventas')->latest()->get();
        // Attach vehicle and client names manually (lightweight join)
        $ventas = $ventas->map(function ($v) {
            $v->vehiculo = DB::table('vehiculos')->where('id', $v->vehiculo_id ?? null)->first();
            $v->cliente = DB::table('clientes')->where('id', $v->cliente_id ?? null)->first();
            $v->vendedor = DB::table('users')->where('id', $v->vendedor_id ?? null)->first();
            return $v;
        });
        return view('sales.index', compact('ventas'));
    }

    public function show($id)
    {
        $venta = DB::table('ventas')->where('id', $id)->firstOrFail();
        $venta->vehiculo = DB::table('vehiculos')->where('id', $venta->vehiculo_id ?? null)->first();
        $venta->cliente = DB::table('clientes')->where('id', $venta->cliente_id ?? null)->first();
        $venta->vendedor = DB::table('users')->where('id', $venta->vendedor_id ?? null)->first();
        return view('sales.show', compact('venta'));
    }
}
