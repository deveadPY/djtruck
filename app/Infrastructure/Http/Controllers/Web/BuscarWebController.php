<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Búsqueda general del encabezado: vehículos (chasis, marca, modelo), clientes (nombre, RUC,
 * teléfono) y ventas (número). Solo busca en los módulos que el usuario puede ver.
 */
class BuscarWebController extends Controller
{
    private const LIMITE = 8;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $resultados = ['vehiculos' => collect(), 'clientes' => collect(), 'ventas' => collect()];

        if (mb_strlen($q) >= 2) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $user = $request->user();

            if ($user->can('vehiculos.ver')) {
                $resultados['vehiculos'] = DB::table('vehiculos')->whereNull('deleted_at')
                    ->where(fn ($w) => $w->where('numero_chasis', 'like', $like)
                        ->orWhere('marca', 'like', $like)->orWhere('modelo', 'like', $like))
                    ->orderByDesc('id')->limit(self::LIMITE)
                    ->get(['id', 'numero_chasis', 'marca', 'modelo', 'anio', 'estado']);
            }
            if ($user->can('clientes.ver')) {
                $resultados['clientes'] = DB::table('clientes')->whereNull('deleted_at')
                    ->where(fn ($w) => $w->where('razon_social', 'like', $like)
                        ->orWhere('nombre_fantasia', 'like', $like)
                        ->orWhere('ruc', 'like', $like)->orWhere('telefono', 'like', $like))
                    ->orderBy('razon_social')->limit(self::LIMITE)
                    ->get(['id', 'razon_social', 'ruc', 'telefono']);
            }
            if ($user->can('ventas.ver')) {
                $resultados['ventas'] = DB::table('ventas as v')
                    ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
                    ->whereNull('v.deleted_at')
                    ->where(fn ($w) => $w->where('v.numero_venta', 'like', $like)->orWhere('c.razon_social', 'like', $like))
                    ->orderByDesc('v.id')->limit(self::LIMITE)
                    ->get(['v.id', 'v.numero_venta', 'v.estado', 'v.fecha_venta', 'v.precio_venta_usd', 'c.razon_social as cliente']);
            }

            // Un único resultado: ir directo (típico al pegar un chasis o un número de venta).
            $total = collect($resultados)->sum(fn ($r) => $r->count());
            if ($total === 1) {
                $ruta = ['vehiculos' => 'vehicles.show', 'clientes' => 'clientes.show', 'ventas' => 'ventas.show'];
                foreach ($resultados as $tipo => $filas) {
                    if ($filas->isNotEmpty()) {
                        return redirect()->route($ruta[$tipo], $filas->first()->id);
                    }
                }
            }
        }

        return view('buscar.index', compact('q', 'resultados'));
    }
}
