<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProveedorWebController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');

        $query = DB::table('proveedores')->whereNull('deleted_at');

        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('razon_social', 'like', "%{$q}%")
                      ->orWhere('ruc_rut_nit', 'like', "%{$q}%")
                      ->orWhere('nombre_fantasia', 'like', "%{$q}%");
            });
        }

        $proveedores = $query->latest()->paginate(25)->withQueryString();
        return view('proveedores.index', compact('proveedores', 'q'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'razon_social' => 'required|string|max:200',
            'ruc_rut_nit' => 'nullable|string|max:30',
            'nombre_fantasia' => 'nullable|string|max:200',
            'pais' => 'required|string|size:2',
            'tipo' => 'required|string',
            'moneda_principal' => 'required|string|size:3',
            'email' => 'nullable|email|max:150',
            'telefono' => 'nullable|string|max:50',
        ]);

        $data['created_by'] = Auth::id();
        $data['activo'] = true;

        $id = DB::table('proveedores')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'proveedor' => ['id' => $id, 'razon_social' => $data['razon_social']]
            ]);
        }

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado.');
    }

    public function edit($id)
    {
        $proveedor = DB::table('proveedores')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'razon_social' => 'required|string|max:200',
            'ruc_rut_nit' => 'nullable|string|max:30',
            'nombre_fantasia' => 'nullable|string|max:200',
            'pais' => 'required|string|size:2',
            'tipo' => 'required|string',
            'moneda_principal' => 'required|string|size:3',
            'email' => 'nullable|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'activo' => 'boolean',
        ]);

        $data['updated_at'] = now();
        DB::table('proveedores')->where('id', $id)->update($data);
        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado.');
    }

    public function destroy($id)
    {
        DB::table('proveedores')->where('id', $id)->update(['deleted_at' => now()]);
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado.');
    }

    public function show($id)
    {
        $proveedor = DB::table('proveedores')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        // Obtener historial de facturas
        $facturas = DB::table('facturas_proveedores')
            ->where('facturas_proveedores.proveedor_id', $id)
            ->whereNull('facturas_proveedores.deleted_at')
            ->leftJoin('vehiculos', 'facturas_proveedores.vehiculo_id', '=', 'vehiculos.id')
            ->select('facturas_proveedores.*', 'vehiculos.numero_chasis', 'vehiculos.marca', 'vehiculos.modelo')
            ->orderBy('facturas_proveedores.fecha_factura', 'desc')
            ->get();

        // Obtener documentos asociados al proveedor
        $documentos = DB::table('documentos')
            ->where('documentable_type', 'proveedores')
            ->where('documentable_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('proveedores.show', compact('proveedor', 'facturas', 'documentos'));
    }
}
