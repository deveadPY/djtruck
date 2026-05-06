<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RepuestosImport;
use App\Exports\ReportExport;

class RepuestoWebController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $stockBajo = $request->boolean('stock_bajo');

        $query = DB::table('stock_repuestos as r')
            ->leftJoin('proveedores as p', 'r.proveedor_id', '=', 'p.id')
            ->select('r.*', 'p.razon_social as proveedor_nombre')
            ->whereNull('r.deleted_at');

        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('r.codigo', 'like', "%{$q}%")
                      ->orWhere('r.descripcion', 'like', "%{$q}%")
                      ->orWhere('r.marca_compatible', 'like', "%{$q}%")
                      ->orWhere('p.razon_social', 'like', "%{$q}%");
            });
        }

        if ($stockBajo) {
            $query->whereRaw('r.stock_actual <= r.stock_minimo')
                  ->where('r.stock_minimo', '>', 0);
        }

        $repuestos = $query->latest()->paginate(25)->withQueryString();

        return view('repuestos.index', compact('repuestos', 'q', 'stockBajo'));
    }

    public function create()
    {
        $proveedores = DB::table('proveedores')->whereNull('deleted_at')->orderBy('razon_social')->get();
        return view('repuestos.create', compact('proveedores'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'             => 'required|string|max:50|unique:stock_repuestos,codigo',
            'descripcion'        => 'required|string|max:300',
            'marca_compatible'   => 'nullable|string|max:80',
            'unidad_medida'      => 'required|string|max:20',
            'stock_minimo'       => 'nullable|numeric|min:0',
            'costo_promedio_usd' => 'required|numeric|min:0',
            'precio_venta_usd'   => 'nullable|numeric|min:0',
            'proveedor_id'       => 'nullable|exists:proveedores,id',
        ]);
        $data['stock_actual'] = 0;
        $data['created_by']   = Auth::id();
        $data['activo']       = true;
        DB::table('stock_repuestos')->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        return redirect()->route('repuestos.index')->with('success', 'Repuesto registrado.');
    }

    public function edit($id)
    {
        $repuesto = DB::table('stock_repuestos')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $proveedores = DB::table('proveedores')->whereNull('deleted_at')->orderBy('razon_social')->get();
        return view('repuestos.edit', compact('repuesto', 'proveedores'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'descripcion'        => 'required|string|max:300',
            'marca_compatible'   => 'nullable|string|max:80',
            'unidad_medida'      => 'required|string|max:20',
            'stock_minimo'       => 'nullable|numeric|min:0',
            'costo_promedio_usd' => 'required|numeric|min:0',
            'precio_venta_usd'   => 'nullable|numeric|min:0',
            'activo'             => 'boolean',
            'proveedor_id'       => 'nullable|exists:proveedores,id',
        ]);
        $data['updated_at'] = now();
        DB::table('stock_repuestos')->where('id', $id)->update($data);
        return redirect()->route('repuestos.index')->with('success', 'Repuesto actualizado.');
    }

    public function destroy($id)
    {
        DB::table('stock_repuestos')->where('id', $id)->update(['deleted_at' => now()]);
        return redirect()->route('repuestos.index')->with('success', 'Repuesto eliminado.');
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:4096',
        ]);

        $import = new RepuestosImport();
        Excel::import($import, $request->file('archivo'));

        $msg = "Importación completada: {$import->getInsertados()} nuevo(s), {$import->getActualizados()} actualizado(s).";

        return redirect()->route('repuestos.index')->with('success', $msg);
    }

    public function exportExcel()
    {
        $rows = DB::table('stock_repuestos as r')
            ->leftJoin('proveedores as p', 'r.proveedor_id', '=', 'p.id')
            ->whereNull('r.deleted_at')
            ->where('r.activo', true)
            ->select([
                'r.codigo', 
                'r.descripcion', 
                'r.marca_compatible', 
                'r.unidad_medida', 
                'p.razon_social as proveedor',
                'r.stock_actual', 
                'r.stock_minimo', 
                'r.costo_promedio_usd', 
                'r.precio_venta_usd'
            ])
            ->orderBy('r.codigo')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $headers = [
            'Código', 
            'Descripción', 
            'Marca Compatible', 
            'U. Medida', 
            'Proveedor',
            'Stock Actual', 
            'Stock Mínimo', 
            'Costo Promedio (USD)', 
            'Precio Venta (USD)'
        ];

        return Excel::download(new ReportExport($rows, $headers), 'productos-' . now()->format('Y-m-d') . '.xlsx');
    }
}
