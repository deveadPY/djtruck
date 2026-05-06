<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Domain\Shared\ValueObjects\Currency;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Purchases\Repositories\PurchaseRepositoryInterface;
use App\Application\Purchases\CreatePurchaseUseCase;
use App\Application\Purchases\CancelPurchaseUseCase;

class CompraWebController extends Controller
{
    public function __construct(
        private readonly CurrencyConverter $currency,
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly CreatePurchaseUseCase $createPurchaseUseCase,
        private readonly CancelPurchaseUseCase $cancelPurchaseUseCase
    ) {}

    public function index(Request $request)
    {
        $q = $request->input('q');
        $compras = $this->purchaseRepository->searchPaginated($q, 25);
        $compras->appends(['q' => $q]);

        return view('compras.index', compact('compras', 'q'));
    }

    public function create()
    {
        $proveedores = DB::table('proveedores')->whereNull('deleted_at')->orderBy('razon_social')->get();
        $productos = DB::table('stock_repuestos')->where('activo', true)->whereNull('deleted_at')->get();
        return view('compras.create', compact('proveedores', 'productos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'proveedor_id'    => 'required|exists:proveedores,id',
            'fecha_compra'    => 'required|date',
            'numero_factura'  => 'nullable|string|max:50',
            'moneda_compra'   => 'required|in:USD,PYG,BRL',
            'tasa_cambio'     => 'required|numeric|min:1',
            'items'           => 'required|array|min:1',
            'items.*.repuesto_id' => 'required|exists:stock_repuestos,id',
            'items.*.cantidad'    => 'required|numeric|min:0.001',
            'items.*.precio_compra'=> 'required|numeric|min:0',
            'items.*.precio_venta_sugerido'=> 'nullable|numeric|min:0',
            'observaciones'   => 'nullable|string',
            'adjuntos'        => 'nullable|array|max:5',
            'adjuntos.*'      => 'file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $adjuntosFiles = $request->file('adjuntos');

        try {
            $this->createPurchaseUseCase->execute($data, $adjuntosFiles);
            return redirect()->route('compras.index')->with('success', 'Compra registrada con éxito, stock actualizado y vinculada a Facturas y Gastos.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al registrar la compra: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $compra = $this->purchaseRepository->findById((int)$id);
        
        if (!$compra) {
            abort(404);
        }

        // To keep view compatibility, we load the relationships manually if needed, or rely on raw DB queries for nested displays if they were already raw
        $compra_raw = DB::table('compras as c')
            ->leftJoin('proveedores as p', 'c.proveedor_id', '=', 'p.id')
            ->leftJoin('users as u', 'c.created_by', '=', 'u.id')
            ->select('c.*', 'p.razon_social as proveedor_nombre', 'u.name as usuario_nombre')
            ->where('c.id', $id)
            ->first();

        $items = DB::table('compra_items as i')
            ->join('stock_repuestos as r', 'i.repuesto_id', '=', 'r.id')
            ->select('i.*', 'r.descripcion', 'r.codigo')
            ->where('i.compra_id', $id)
            ->get();

        $documentos = DB::table('documentos')
            ->where('documentable_type', 'compras')
            ->where('documentable_id', $id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        return view('compras.show', ['compra' => $compra_raw, 'items' => $items, 'documentos' => $documentos]);
    }

    public function destroy($id)
    {
        $success = $this->cancelPurchaseUseCase->execute((int)$id);

        if ($success) {
            return redirect()->route('compras.index')->with('success', 'Compra anulada con éxito. Se ha revertido el stock, el movimiento de caja y la factura asociada.');
        }

        return redirect()->route('compras.index')->with('error', 'La compra ya fue anulada anteriormente o no existe.');
    }
}
