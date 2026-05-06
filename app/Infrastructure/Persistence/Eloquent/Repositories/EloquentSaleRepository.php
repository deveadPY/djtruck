<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Support\Facades\DB;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function findById(int $id): ?SaleModel
    {
        return SaleModel::find($id);
    }

    public function create(array $data): SaleModel
    {
        return SaleModel::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $sale = SaleModel::find($id);
        if (!$sale) return false;
        return $sale->update($data);
    }

    public function delete(int $id): bool
    {
        $sale = SaleModel::find($id);
        if (!$sale) return false;
        return $sale->delete();
    }

    public function getLatest(int $limit = 20)
    {
        return SaleModel::with(['cliente', 'vehiculo'])
            ->latest()
            ->paginate($limit);
    }
}
