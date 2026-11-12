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
        return VehicleModel::with(['proveedor', 'imagenes'])->find($id);
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

    public function searchPaginated(?string $searchQuery, int $limit = 15, ?string $estado = null, ?string $orden = null): LengthAwarePaginator
    {
        // Equivalent to the raw DB query in index() but using Eloquent
        $query = VehicleModel::select([
                'vehiculos.*',
                'ventas.precio_venta_usd as venta_precio_usd',
                'ventas.precio_venta_moneda as venta_precio_moneda',
                'ventas.moneda_venta as venta_moneda'
            ])
            // Foto de portada en la misma consulta (evita N+1): la marcada como portada, si no la primera por orden.
            ->selectSub(
                DB::table('vehiculo_imagenes')->select('ruta')
                    ->whereColumn('vehiculo_imagenes.vehiculo_id', 'vehiculos.id')
                    ->whereNull('vehiculo_imagenes.deleted_at')
                    ->orderByDesc('es_portada')->orderBy('orden')->orderBy('id')
                    ->limit(1),
                'portada_ruta'
            )
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

        if ($estado === 'STOCK') {
            $query->whereNotIn('vehiculos.estado', ['VENDIDO', 'BAJA']);
        } elseif ($estado) {
            $query->where('vehiculos.estado', $estado);
        }

        // Orden por lista blanca; lo desconocido cae en "recientes". Los precios vacíos van al final.
        match ($orden) {
            'antiguos'    => $query->oldest('vehiculos.created_at'),
            'precio_asc'  => $query->orderByRaw('vehiculos.precio_contado_usd IS NULL')->orderBy('vehiculos.precio_contado_usd'),
            'precio_desc' => $query->orderByRaw('vehiculos.precio_contado_usd IS NULL')->orderByDesc('vehiculos.precio_contado_usd'),
            'anio_desc'   => $query->orderByDesc('vehiculos.anio'),
            default       => $query->latest('vehiculos.created_at'),
        };

        return $query->orderByDesc('vehiculos.id')->paginate($limit);
    }

    public function getAvailableForSale(): \Illuminate\Database\Eloquent\Collection
    {
        return VehicleModel::select(['id', 'marca', 'modelo', 'anio', 'numero_chasis', 'estado', 'costo_origen_usd', 'total_gastos_usd'])
            ->whereIn('estado', ['DISPONIBLE', 'RESERVADO'])
            ->orderBy('marca')
            ->get();
    }
}
