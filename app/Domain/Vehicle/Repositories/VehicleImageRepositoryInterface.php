<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Repositories;

use Illuminate\Support\Collection;

interface VehicleImageRepositoryInterface
{
    public function getByVehicle(int $vehicleId): Collection;

    public function findById(int $imageId): ?object;

    public function insert(int $vehicleId, array $data): int;

    public function softDelete(int $imageId, ?int $userId = null): bool;

    public function clearCoversForVehicle(int $vehicleId): void;

    public function setAsCover(int $imageId): bool;

    public function getNextOrderForVehicle(int $vehicleId): int;

    public function getFirstActiveForVehicle(int $vehicleId): ?object;
}
