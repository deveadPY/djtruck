<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentVehicleRepository implements VehicleRepositoryInterface
{
    public function findById(int $id): ?VehicleModel
    {
        return VehicleModel::find($id);
    }

    public function create(array $data): VehicleModel
    {
        return VehicleModel::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $vehicle = VehicleModel::find($id);
        if (!$vehicle) return false;
        return $vehicle->update($data);
    }

    public function delete(int $id, int $userId = null): bool
    {
        $vehicle = VehicleModel::find($id);
        if (!$vehicle) return false;
        
        if ($userId) {
            $vehicle->deleted_by = $userId;
            $vehicle->save();
        }
        
        return $vehicle->delete();
    }

    public function searchPaginated(?string $searchQuery, int $limit = 15): LengthAwarePaginator
    {
        // Equivalent to the raw DB query in index() but using Eloquent
        $query = VehicleModel::select([
                'vehiculos.*', 
                'ventas.precio_venta_usd as venta_precio_usd',
                'ventas.precio_venta_moneda as venta_precio_moneda',
                'ventas.moneda_venta as venta_moneda'
            ])
            ->leftJoin('ventas', function ($join) {
                $join->on('vehiculos.id', '=', 'ventas.vehiculo_id')
                     ->whereNull('ventas.deleted_at');
            });

        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('vehiculos.marca', 'like', "%{$searchQuery}%")
                  ->orWhere('vehiculos.modelo', 'like', "%{$searchQuery}%")
                  ->orWhere('vehiculos.numero_chasis', 'like', "%{$searchQuery}%")
                  ->orWhere('vehiculos.numero_motor', 'like', "%{$searchQuery}%");
            });
        }

        return $query->latest('vehiculos.created_at')->paginate($limit);
    }

    public function getAvailableForSale()
    {
        return VehicleModel::whereIn('estado', ['DISPONIBLE', 'RESERVADO'])
            ->get();
    }
}
