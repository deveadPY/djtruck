<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Vehicle\Repositories\VehicleImageRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EloquentVehicleImageRepository implements VehicleImageRepositoryInterface
{
    private const TABLE = 'vehiculo_imagenes';

    public function getByVehicle(int $vehicleId): Collection
    {
        return collect(
            DB::table(self::TABLE)
                ->where('vehiculo_id', $vehicleId)
                ->whereNull('deleted_at')
                ->orderBy('orden')
                ->get()
        );
    }

    public function findById(int $imageId): ?object
    {
        return DB::table(self::TABLE)
            ->where('id', $imageId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function insert(int $vehicleId, array $data): int
    {
        return DB::table(self::TABLE)->insertGetId(array_merge($data, [
            'vehiculo_id' => $vehicleId,
            'created_by'  => $data['created_by'] ?? Auth::id(),
            'created_at'  => $data['created_at'] ?? now(),
            'updated_at'  => $data['updated_at'] ?? now(),
        ]));
    }

    public function softDelete(int $imageId, ?int $userId = null): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $imageId)
            ->update([
                'deleted_at' => now(),
                'deleted_by' => $userId ?? Auth::id(),
            ]) > 0;
    }

    public function clearCoversForVehicle(int $vehicleId): void
    {
        DB::table(self::TABLE)
            ->where('vehiculo_id', $vehicleId)
            ->update(['es_portada' => false]);
    }

    public function setAsCover(int $imageId): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $imageId)
            ->update([
                'es_portada' => true,
                'updated_at' => now(),
            ]) > 0;
    }

    public function getNextOrderForVehicle(int $vehicleId): int
    {
        $max = DB::table(self::TABLE)
            ->where('vehiculo_id', $vehicleId)
            ->whereNull('deleted_at')
            ->max('orden');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    public function getFirstActiveForVehicle(int $vehicleId): ?object
    {
        return DB::table(self::TABLE)
            ->where('vehiculo_id', $vehicleId)
            ->whereNull('deleted_at')
            ->orderBy('orden')
            ->first();
    }
}
