<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Domain\Shared\ValueObjects\Currency;
use App\Infrastructure\Currency\CurrencyConverter;

class CompraWebController extends Controller
{
    public function __construct(
        private readonly CurrencyConverter $currency
    ) {}

    public function index(Request $request)
    {
        $q = $request->input('q');
        $query = DB::table('compras as c')
            ->leftJoin('proveedores as p', 'c.proveedor_id', '=', 'p.id')
            ->select('c.*', 'p.razon_social as proveedor_nombre')
            ->whereNull('c.deleted_at');

        if ($q) {
            $query->where(function($query) use ($q) {
                $query->where('c.numero_factura', 'like', "%{$q}%")
                      ->orWhere('p.razon_social', 'like', "%{$q}%");
            });
        }

        $compras = $query->latest('c.fecha_compra')->paginate(25)->withQueryString();

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
        $v = $request->validate([
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

        $adjuntosFiles = $request->file('adjuntos') ?? [];

        return DB::transaction(function () use ($v, $adjuntosFiles) {
            $moneda = Currency::from($v['moneda_compra']);
            $tasa = (float) $v['tasa_cambio'];
            
            $totalMoneda = 0;
            $totalUsd = 0;

            // Pre-calcular totales
            foreach ($v['items'] as $item) {
                $subtotalMoneda = $item['cantidad'] * $item['precio_compra'];
                $totalMoneda += $subtotalMoneda;
            }

            if ($moneda === Currency::USD) {
                $totalUsd = $totalMoneda;
            } else {
                $totalUsd = $totalMoneda / $tasa;
            }

            // 1. Crear la Compra
            $compraId = DB::table('compras')->insertGetId([
                'proveedor_id'       => $v['proveedor_id'],
                'numero_factura'     => $v['numero_factura'],
                'fecha_compra'       => $v['fecha_compra'],
                'moneda_compra'      => $v['moneda_compra'],
                'monto_total_moneda' => $totalMoneda,
                'monto_total_usd'    => round($totalUsd, 2),
                'tasa_cambio'        => $tasa,
                'observaciones'      => $v['observaciones'],
                'caja_id'            => DB::table('cajas')->where('codigo', 'CAJA_CAPITAL')->value('id'),
                'created_by'         => Auth::id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // 2. Insertar Items y Actualizar Stock
            foreach ($v['items'] as $itemData) {
                $precioCompraUsd = ($moneda === Currency::USD) 
                    ? $itemData['precio_compra'] 
                    : ($itemData['precio_compra'] / $tasa);
                
                $subtotalItemUsd = $itemData['cantidad'] * $precioCompraUsd;

                DB::table('compra_items')->insert([
                    'compra_id'           => $compraId,
                    'repuesto_id'         => $itemData['repuesto_id'],
                    'cantidad'            => $itemData['cantidad'],
                    'precio_compra_moneda'=> $itemData['precio_compra'],
                    'precio_compra_usd'   => round($precioCompraUsd, 2),
                    'precio_venta_sugerido_usd' => $itemData['precio_venta_sugerido'] ?? null,
                    'subtotal_usd'        => round($subtotalItemUsd, 2),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // Actualizar Stock y Precios en el producto
                $producto = DB::table('stock_repuestos')->where('id', $itemData['repuesto_id'])->first();
                $nuevoStock = $producto->stock_actual + $itemData['cantidad'];
                
                $updateData = [
                    'stock_actual'       => $nuevoStock,
                    'costo_promedio_usd' => round($precioCompraUsd, 2),
                    'updated_at'         => now(),
                ];

                if (!empty($itemData['precio_venta_sugerido'])) {
                    $updateData['precio_venta_usd'] = $itemData['precio_venta_sugerido'];
                }

                DB::table('stock_repuestos')->where('id', $itemData['repuesto_id'])->update($updateData);
            }

            // 3. Registrar Movimiento en Caja Capital (EGRESO)
            $proveedor = DB::table('proveedores')->where('id', $v['proveedor_id'])->first();
            $movimientoId = DB::table('movimientos_caja')->insertGetId([
                'caja_id'    => DB::table('cajas')->where('codigo', 'CAJA_CAPITAL')->value('id'),
                'tipo'       => 'EGRESO',
                'concepto'   => "Compra de productos - Fac. " . ($v['numero_factura'] ?? 'S/N') . " - Prov: " . $proveedor->razon_social,
                'moneda'     => $v['moneda_compra'],
                'monto'      => $totalMoneda,
                'monto_usd'  => round($totalUsd, 2),
                'ref_type'      => 'compra',
                'referencia_id' => $compraId,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Asociar con Facturas y Gastos para visualización
            $facturaId = DB::table('facturas_proveedores')->insertGetId([
                'proveedor_id'   => $v['proveedor_id'],
                'numero_factura' => $v['numero_factura'] ?? ('COMP-' . $compraId),
                'fecha_factura'  => $v['fecha_compra'],
                'destino'        => 'REPOSICION',
                'compra_id'      => $compraId,
                'moneda'         => $v['moneda_compra'],
                'subtotal'       => $totalMoneda,
                'impuestos'      => 0,
                'total_usd'      => round($totalUsd, 2),
                'estado'         => 'PAGADA',
                'descripcion'    => "Compra de repuestos vinculada. " . ($v['observaciones'] ?? ''),
                'created_by'     => Auth::id(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 5. Guardar documentos adjuntos y sincronizar con Factura
            if (!empty($adjuntosFiles)) {
                $uploadDir = 'uploads/documentos/compras/' . $compraId;
                $uploadDirFactura = 'uploads/documentos/facturas_proveedores/' . $facturaId;

                foreach ($adjuntosFiles as $archivo) {
                    $nombreOriginal = $archivo->getClientOriginalName();
                    $mimeType = $archivo->getClientMimeType();
                    $tamano = $archivo->getSize();
                    $nombre = time() . '_' . uniqid() . '_' . $nombreOriginal;

                    // Asegurar que existan los directorios
                    if (!is_dir(public_path($uploadDir))) {
                        mkdir(public_path($uploadDir), 0777, true);
                    }
                    if (!is_dir(public_path($uploadDirFactura))) {
                        mkdir(public_path($uploadDirFactura), 0777, true);
                    }

                    // Carpeta de compras
                    $archivo->move(public_path($uploadDir), $nombre);
                    
                    // Copiar para factura (para que sean independientes si se borra una)
                    copy(public_path($uploadDir . '/' . $nombre), public_path($uploadDirFactura . '/' . $nombre));

                    // Registrar en DB para Compra
                    DB::table('documentos')->insert([
                        'documentable_type' => 'compras',
                        'documentable_id'   => $compraId,
                        'ruta'              => $uploadDir . '/' . $nombre,
                        'nombre_original'   => $nombreOriginal,
                        'mime_type'         => $mimeType,
                        'tamano_bytes'      => $tamano,
                        'created_by'        => Auth::id(),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                    // Registrar en DB para Factura
                    DB::table('documentos')->insert([
                        'documentable_type' => 'facturas_proveedores',
                        'documentable_id'   => $facturaId,
                        'ruta'              => $uploadDirFactura . '/' . $nombre,
                        'nombre_original'   => $nombreOriginal,
                        'mime_type'         => $mimeType,
                        'tamano_bytes'      => $tamano,
                        'created_by'        => Auth::id(),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }

            return redirect()->route('compras.index')->with('success', 'Compra registrada con éxito, stock actualizado y vinculada a Facturas y Gastos.');
        });
    }

    public function show($id)
    {
        $compra = DB::table('compras as c')
            ->leftJoin('proveedores as p', 'c.proveedor_id', '=', 'p.id')
            ->leftJoin('users as u', 'c.created_by', '=', 'u.id')
            ->select('c.*', 'p.razon_social as proveedor_nombre', 'u.name as usuario_nombre')
            ->where('c.id', $id)
            ->firstOrFail();

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

        return view('compras.show', compact('compra', 'items', 'documentos'));
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $compra = DB::table('compras')->where('id', $id)->firstOrFail();
            
            // Si ya está borrada, ignorar
            if ($compra->deleted_at) {
                return redirect()->route('compras.index')->with('error', 'La compra ya fue anulada anteriormente.');
            }

            $items = DB::table('compra_items')->where('compra_id', $id)->get();

            // 1. Revertir Stock
            foreach ($items as $item) {
                DB::table('stock_repuestos')
                    ->where('id', $item->repuesto_id)
                    ->decrement('stock_actual', $item->cantidad);
            }

            // 2. Anular Movimiento de Caja Capital
            DB::table('movimientos_caja')
                ->where('ref_type', 'compra')
                ->where('referencia_id', $id)
                ->update([
                    'deleted_at' => now(),
                    'concepto' => '[ANULADO] ' . DB::table('movimientos_caja')
                        ->where('ref_type', 'compra')
                        ->where('referencia_id', $id)
                        ->value('concepto')
                ]);

            // 3. Anular Compra (Soft Delete)
            DB::table('compras')->where('id', $id)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
                'estado'     => 'ANULADO'
            ]);

            // 4. Anular Factura asociada
            DB::table('facturas_proveedores')
                ->where('compra_id', $id)
                ->update([
                    'estado' => 'ANULADO',
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);

            return redirect()->route('compras.index')->with('success', 'Compra anulada con éxito. Se ha revertido el stock, el movimiento de caja y la factura asociada.');
        });
    }
}
