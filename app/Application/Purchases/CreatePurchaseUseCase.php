<?php

declare(strict_types=1);

namespace App\Application\Purchases;

use App\Domain\Purchases\Repositories\PurchaseRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreatePurchaseUseCase
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function execute(array $data, ?array $adjuntosFiles = null): PurchaseModel
    {
        return DB::transaction(function () use ($data, $adjuntosFiles) {
            $moneda = Currency::from($data['moneda_compra']);
            $tasa = (float) $data['tasa_cambio'];
            
            $totalMoneda = 0;
            $totalUsd = 0;

            // Pre-calcular totales
            foreach ($data['items'] as $item) {
                $subtotalMoneda = $item['cantidad'] * $item['precio_compra'];
                $totalMoneda += $subtotalMoneda;
            }

            if ($moneda === Currency::USD) {
                $totalUsd = $totalMoneda;
            } else {
                $totalUsd = $totalMoneda / $tasa;
            }

            // 1. Crear la Compra a través del Repositorio
            $purchase = $this->purchaseRepository->create([
                'proveedor_id'       => $data['proveedor_id'],
                'numero_factura'     => $data['numero_factura'],
                'fecha_compra'       => $data['fecha_compra'],
                'moneda_compra'      => $data['moneda_compra'],
                'monto_total_moneda' => $totalMoneda,
                'monto_total_usd'    => round($totalUsd, 2),
                'tasa_cambio'        => $tasa,
                'observaciones'      => $data['observaciones'],
                'caja_id'            => DB::table('cajas')->where('codigo', 'CAJA_CAPITAL')->value('id'),
                'created_by'         => Auth::id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $compraId = $purchase->id;

            // 2. Insertar Items y Actualizar Stock
            foreach ($data['items'] as $itemData) {
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
            $proveedor = DB::table('proveedores')->where('id', $data['proveedor_id'])->first();
            DB::table('movimientos_caja')->insert([
                'caja_id'    => DB::table('cajas')->where('codigo', 'CAJA_CAPITAL')->value('id'),
                'tipo'       => 'EGRESO',
                'concepto'   => "Compra de productos - Fac. " . ($data['numero_factura'] ?? 'S/N') . " - Prov: " . $proveedor->razon_social,
                'moneda'     => $data['moneda_compra'],
                'monto'      => $totalMoneda,
                'monto_usd'  => round($totalUsd, 2),
                'ref_type'   => 'compra',
                'referencia_id' => $compraId,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Asociar con Facturas y Gastos para visualización
            $facturaId = DB::table('facturas_proveedores')->insertGetId([
                'proveedor_id'   => $data['proveedor_id'],
                'numero_factura' => $data['numero_factura'] ?? ('COMP-' . $compraId),
                'fecha_factura'  => $data['fecha_compra'],
                'destino'        => 'REPOSICION',
                'compra_id'      => $compraId,
                'moneda'         => $data['moneda_compra'],
                'subtotal'       => $totalMoneda,
                'impuestos'      => 0,
                'total_usd'      => round($totalUsd, 2),
                'estado'         => 'PAGADA',
                'descripcion'    => "Compra de repuestos vinculada. " . ($data['observaciones'] ?? ''),
                'created_by'     => Auth::id(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 5. Guardar documentos adjuntos y sincronizar con Factura
            if ($adjuntosFiles && count($adjuntosFiles) > 0) {
                $uploadDir = 'uploads/documentos/compras/' . $compraId;
                $uploadDirFactura = 'uploads/documentos/facturas_proveedores/' . $facturaId;

                if (!is_dir(public_path($uploadDir))) {
                    mkdir(public_path($uploadDir), 0777, true);
                }
                if (!is_dir(public_path($uploadDirFactura))) {
                    mkdir(public_path($uploadDirFactura), 0777, true);
                }

                foreach ($adjuntosFiles as $archivo) {
                    $nombreOriginal = $archivo->getClientOriginalName();
                    $mimeType = $archivo->getClientMimeType();
                    $tamano = $archivo->getSize();
                    $nombre = time() . '_' . uniqid() . '_' . $nombreOriginal;

                    $archivo->move(public_path($uploadDir), $nombre);
                    copy(public_path($uploadDir . '/' . $nombre), public_path($uploadDirFactura . '/' . $nombre));

                    DB::table('documentos')->insert([
                        ['documentable_type' => 'compras', 'documentable_id' => $compraId, 'ruta' => $uploadDir . '/' . $nombre, 'nombre_original' => $nombreOriginal, 'mime_type' => $mimeType, 'tamano_bytes' => $tamano, 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()],
                        ['documentable_type' => 'facturas_proveedores', 'documentable_id' => $facturaId, 'ruta' => $uploadDirFactura . '/' . $nombre, 'nombre_original' => $nombreOriginal, 'mime_type' => $mimeType, 'tamano_bytes' => $tamano, 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()],
                    ]);
                }
            }

            return $purchase;
        });
    }
}
