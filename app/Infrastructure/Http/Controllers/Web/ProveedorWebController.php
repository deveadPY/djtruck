<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Application\Suppliers\CreateSupplierDTO;
use App\Application\Suppliers\CreateSupplierUseCase;
use App\Domain\Suppliers\Exceptions\DuplicateSupplierException;
use App\Domain\Suppliers\Exceptions\InvalidSupplierDataException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProveedorWebController extends Controller
{
    public function __construct(
        private readonly CreateSupplierUseCase $createSupplierUseCase,
    ) {}

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

        try {
            $supplier = $this->createSupplierUseCase->execute(CreateSupplierDTO::fromArray($data));
        } catch (DuplicateSupplierException | InvalidSupplierDataException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado.');
    }

    /**
     * Endpoint AJAX/JSON para crear proveedor inline desde otro formulario
     * (ej: form de repuestos cuando el proveedor no existe).
     *
     * POST /proveedores/quick-create
     * Sólo acepta JSON, devuelve {success, proveedor: {id, razon_social, ruc_rut_nit, tipo}}.
     */
    public function quickStore(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'razon_social'     => 'required|string|max:200',
                'ruc_rut_nit'      => 'nullable|string|max:30',
                'nombre_fantasia'  => 'nullable|string|max:200',
                'pais'             => 'nullable|string|size:2',
                'tipo'             => 'nullable|in:FABRICANTE,DISTRIBUIDOR,IMPORTADOR,SERVICIO,OTRO',
                'moneda_principal' => 'nullable|string|size:3',
                'email'            => 'nullable|email|max:150',
                'telefono'         => 'nullable|string|max:50',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Defaults sensatos para el quick-create (mínima fricción)
        $data['pais']             = $data['pais']             ?? 'PY';
        $data['tipo']             = $data['tipo']             ?? 'DISTRIBUIDOR';
        $data['moneda_principal'] = $data['moneda_principal'] ?? 'USD';

        try {
            $supplier = $this->createSupplierUseCase->execute(CreateSupplierDTO::fromArray($data));
        } catch (DuplicateSupplierException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => ['ruc_rut_nit' => [$e->getMessage()]],
            ], 409);
        } catch (InvalidSupplierDataException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Proveedor creado correctamente.',
            'proveedor' => [
                'id'           => $supplier->getId()->value(),
                'razon_social' => $supplier->getRazonSocial(),
                'ruc_rut_nit'  => $supplier->getRucRutNit(),
            ],
        ], 201);
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
