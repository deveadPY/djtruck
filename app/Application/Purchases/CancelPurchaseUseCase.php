<?php

declare(strict_types=1);

namespace App\Application\Purchases;

use App\Domain\Purchases\Repositories\PurchaseRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CancelPurchaseUseCase
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function execute(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $compra = $this->purchaseRepository->findById($id);
            
            if (!$compra || $compra->deleted_at) {
                return false; // Ya anulada o no existe
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

            // 3. Anular Compra (Soft Delete) a través del Repositorio
            $this->purchaseRepository->update($id, [
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

            return true;
        });
    }
}
