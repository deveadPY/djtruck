<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Purchases\Repositories\PurchaseRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentPurchaseRepository implements PurchaseRepositoryInterface
{
    public function findById(int $id): ?PurchaseModel
    {
        return PurchaseModel::find($id);
    }

    public function create(array $data): PurchaseModel
    {
        return PurchaseModel::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $purchase = PurchaseModel::find($id);
        if (!$purchase) return false;
        return $purchase->update($data);
    }

    public function delete(int $id): bool
    {
        $purchase = PurchaseModel::find($id);
        if (!$purchase) return false;
        return $purchase->delete();
    }

    public function searchPaginated(?string $searchQuery, int $limit = 25): LengthAwarePaginator
    {
        $query = PurchaseModel::select('compras.*', 'proveedores.razon_social as proveedor_nombre')
            ->leftJoin('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->whereNull('compras.deleted_at');

        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('compras.numero_factura', 'like', "%{$searchQuery}%")
                  ->orWhere('proveedores.razon_social', 'like', "%{$searchQuery}%");
            });
        }

        return $query->latest('compras.fecha_compra')->paginate($limit);
    }
}
