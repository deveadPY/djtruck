<?php

declare(strict_types=1);

namespace App\Application\Purchases;

use App\Domain\Purchases\Repositories\PurchaseRepositoryInterface;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class UpdatePurchaseUseCase
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function execute(UpdatePurchaseDTO $dto): bool
    {
        $purchase = $this->purchaseRepository->findById($dto->id);
        if (!$purchase || $purchase->deleted_at) {
            throw new RuntimeException('La compra no existe o ya fue anulada.');
        }

        $oldValues = $purchase->toArray();

        return DB::transaction(function () use ($dto, $purchase, $oldValues) {
            // 1. Revertir stock de los items anteriores
            $oldItems = DB::table('compra_items')->where('compra_id', $dto->id)->get();
            foreach ($oldItems as $item) {
                DB::table('stock_repuestos')
                    ->where('id', $item->repuesto_id)
                    ->decrement('stock_actual', $item->cantidad);
            }

            // 2. Eliminar físicamente los items anteriores de la tabla detalle
            DB::table('compra_items')
                ->where('compra_id', $dto->id)
                ->delete();

            // 3. Anular movimiento de caja anterior
            DB::table('movimientos_caja')
                ->where('ref_type', 'compra')
                ->where('referencia_id', $dto->id)
                ->update([
                    'deleted_at' => now(),
                    'concepto' => DB::raw("CONCAT('[ANULADO POR EDICIÓN] ', concepto)"),
                    'updated_at' => now(),
                ]);

            // 4. Recalcular totales
            $moneda = Currency::from($dto->monedaCompra);
            $tasa   = $dto->tasaCambio;

            $totalMoneda = 0;
            foreach ($dto->items as $item) {
                $totalMoneda += $item['cantidad'] * $item['precio_compra'];
            }

            $totalUsd = ($moneda === Currency::USD) ? $totalMoneda : ($totalMoneda / $tasa);

            // 5. Update purchase record
            $this->purchaseRepository->update($dto->id, [
                'proveedor_id'       => $dto->proveedorId,
                'numero_factura'     => $dto->numeroFactura,
                'fecha_compra'       => $dto->fechaCompra,
                'moneda_compra'      => $dto->monedaCompra,
                'monto_total_moneda' => $totalMoneda,
                'monto_total_usd'    => round($totalUsd, 2),
                'tasa_cambio'        => $tasa,
                'observaciones'      => $dto->observaciones,
                'updated_by'         => Auth::id(),
                'updated_at'         => now(),
            ]);

            // 6. Insertar nuevos items y actualizar stock
            foreach ($dto->items as $itemData) {
                $precioCompraUsd = ($moneda === Currency::USD)
                    ? $itemData['precio_compra']
                    : ($itemData['precio_compra'] / $tasa);

                $subtotalItemUsd = $itemData['cantidad'] * $precioCompraUsd;

                DB::table('compra_items')->insert([
                    'compra_id'              => $dto->id,
                    'repuesto_id'            => $itemData['repuesto_id'],
                    'cantidad'               => $itemData['cantidad'],
                    'precio_compra_moneda'   => $itemData['precio_compra'],
                    'precio_compra_usd'      => round($precioCompraUsd, 2),
                    'precio_venta_sugerido_usd' => $itemData['precio_venta_sugerido'] ?? null,
                    'subtotal_usd'           => round($subtotalItemUsd, 2),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

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

            // 7. Nuevo movimiento en caja capital
            $proveedor = DB::table('proveedores')->where('id', $dto->proveedorId)->first();
            DB::table('movimientos_caja')->insert([
                'caja_id'       => DB::table('cajas')->where('codigo', 'CAJA_CAPITAL')->value('id'),
                'tipo'          => 'EGRESO',
                'concepto'      => "Compra de productos [Editada] - Fac. " . ($dto->numeroFactura ?: 'S/N') . " - Prov: " . $proveedor->razon_social,
                'moneda'        => $dto->monedaCompra,
                'monto'         => $totalMoneda,
                'monto_usd'     => round($totalUsd, 2),
                'ref_type'      => 'compra',
                'referencia_id' => $dto->id,
                'created_by'    => Auth::id(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // 8. Actualizar factura asociada
            DB::table('facturas_proveedores')
                ->where('compra_id', $dto->id)
                ->update([
                    'proveedor_id'   => $dto->proveedorId,
                    'numero_factura' => $dto->numeroFactura ?: ('COMP-' . $dto->id),
                    'fecha_factura'  => $dto->fechaCompra,
                    'moneda'         => $dto->monedaCompra,
                    'subtotal'       => $totalMoneda,
                    'total_usd'      => round($totalUsd, 2),
                    'descripcion'    => "Compra de repuestos vinculada [Editada]. " . ($dto->observaciones ?? ''),
                    'updated_at'     => now(),
                ]);

            // 9. Adjuntos nuevos
            if (count($dto->adjuntos) > 0) {
                $facturaId = DB::table('facturas_proveedores')
                    ->where('compra_id', $dto->id)
                    ->value('id');

                $uploadDir = 'uploads/documentos/compras/' . $dto->id;
                $uploadDirFactura = 'uploads/documentos/facturas_proveedores/' . $facturaId;

                if (!is_dir(public_path($uploadDir))) {
                    mkdir(public_path($uploadDir), 0777, true);
                }
                if (!is_dir(public_path($uploadDirFactura))) {
                    mkdir(public_path($uploadDirFactura), 0777, true);
                }

                foreach ($dto->adjuntos as $archivo) {
                    $nombreOriginal = $archivo->getClientOriginalName();
                    $mimeType = $archivo->getClientMimeType();
                    $tamano = $archivo->getSize();
                    $nombre = time() . '_' . uniqid() . '_' . $nombreOriginal;

                    $archivo->move(public_path($uploadDir), $nombre);
                    copy(public_path($uploadDir . '/' . $nombre), public_path($uploadDirFactura . '/' . $nombre));

                    DB::table('documentos')->insert([
                        ['documentable_type' => 'compras', 'documentable_id' => $dto->id, 'ruta' => $uploadDir . '/' . $nombre, 'nombre_original' => $nombreOriginal, 'mime_type' => $mimeType, 'tamano_bytes' => $tamano, 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()],
                        ['documentable_type' => 'facturas_proveedores', 'documentable_id' => $facturaId, 'ruta' => $uploadDirFactura . '/' . $nombre, 'nombre_original' => $nombreOriginal, 'mime_type' => $mimeType, 'tamano_bytes' => $tamano, 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()],
                    ]);
                }
            }

            // 10. Auditoría
            $newValues = $this->purchaseRepository->findById($dto->id)?->toArray();
            $this->auditar('UPDATE_PURCHASE', 'compra', $dto->id, $oldValues, $newValues ?: [], Auth::id(), request()->ip());

            return true;
        });
    }

    private function auditar(
        string $action,
        string $entityType,
        int $entityId,
        array $oldValues,
        array $newValues,
        ?int $userId,
        ?string $ipAddress,
    ): void {
        DB::table('audit_logs')->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => json_encode($oldValues),
            'new_values'  => json_encode($newValues),
            'metadata'    => null,
            'ip_address'  => $ipAddress,
            'created_at'  => now(),
        ]);
    }
}
