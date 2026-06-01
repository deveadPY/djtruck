<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function findById(int $id): ?SaleModel
    {
        return SaleModel::with([
            'cliente:id,razon_social,ruc,email,telefono,direccion',
            'vehiculo',
            'vendedor:id,name,email',
        ])->find($id);
    }

    public function findByIdWithTrashed(int $id): ?SaleModel
    {
        return SaleModel::withTrashed()->with([
            'cliente:id,razon_social,ruc,email,telefono,direccion',
            'vehiculo',
            'vendedor:id,name,email',
        ])->find($id);
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
        return SaleModel::with([
            'cliente:id,razon_social,ruc,nombre_fantasia',
            'vehiculo:id,marca,modelo,numero_chasis,anio,estado',
            'vendedor:id,name',
        ])
            ->latest()
            ->paginate($limit);
    }

    public function addItems(int $saleId, array $items): void
    {
        $rows = array_map(function (array $item) use ($saleId) {
            return array_merge($item, [
                'venta_id' => $saleId,
                'created_at' => $item['created_at'] ?? now(),
                'updated_at' => $item['updated_at'] ?? now(),
            ]);
        }, $items);

        DB::table('venta_items')->insert($rows);
    }

    public function getItems(int $saleId): Collection
    {
        return collect(DB::table('venta_items')->where('venta_id', $saleId)->get());
    }

    public function removeItems(int $saleId): void
    {
        DB::table('venta_items')->where('venta_id', $saleId)->delete();
    }

    public function addPayment(int $saleId, array $paymentDetail): int
    {
        return DB::table('detalles_pago')->insertGetId(array_merge($paymentDetail, [
            'venta_id' => $saleId,
            'created_by' => $paymentDetail['created_by'] ?? Auth::id(),
            'created_at' => $paymentDetail['created_at'] ?? now(),
            'updated_at' => $paymentDetail['updated_at'] ?? now(),
        ]));
    }

    public function getPayments(int $saleId): Collection
    {
        return collect(DB::table('detalles_pago')->where('venta_id', $saleId)->get());
    }

    public function removePayments(int $saleId): void
    {
        DB::table('detalles_pago')->where('venta_id', $saleId)->delete();
    }

    public function addInstallmentPlan(int $saleId, array $planData): int
    {
        return DB::table('planes_cuotas')->insertGetId(array_merge($planData, [
            'venta_id' => $saleId,
            'created_by' => $planData['created_by'] ?? Auth::id(),
            'created_at' => $planData['created_at'] ?? now(),
            'updated_at' => $planData['updated_at'] ?? now(),
        ]));
    }

    public function getPlan(int $saleId): ?object
    {
        return DB::table('planes_cuotas')->where('venta_id', $saleId)->first();
    }

    public function removePlan(int $saleId): void
    {
        $planIds = DB::table('planes_cuotas')->where('venta_id', $saleId)->pluck('id');
        DB::table('cuotas')->whereIn('plan_cuotas_id', $planIds)->delete();
        DB::table('planes_cuotas')->where('venta_id', $saleId)->delete();
    }

    public function getDocuments(int $saleId): Collection
    {
        return collect(
            DB::table('documentos')
                ->where('documentable_type', 'ventas')
                ->where('documentable_id', $saleId)
                ->whereNull('deleted_at')
                ->latest()
                ->get()
        );
    }
}
