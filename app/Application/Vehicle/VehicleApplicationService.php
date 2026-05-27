<?php

declare(strict_types=1);

namespace App\Application\Vehicle;

use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleApplicationService
{
    public function __construct(
        private readonly CreateVehicleUseCase $createVehicleUseCase,
        private readonly UpdateVehicleUseCase $updateVehicleUseCase,
        private readonly VehicleRepositoryInterface $vehicleRepository
    ) {}

    public function create(CreateVehicleDTO $dto): VehicleModel
    {
        return $this->createVehicleUseCase->execute($dto);
    }

    public function update(int $id, array $data, ?array $imagenes = null): bool
    {
        return $this->updateVehicleUseCase->execute($id, $data, $imagenes);
    }

    public function findById(int $id): ?VehicleModel
    {
        return $this->vehicleRepository->findById($id);
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        return $this->vehicleRepository->delete($id, $userId);
    }

    public function search(?string $query, int $limit = 15): LengthAwarePaginator
    {
        return $this->vehicleRepository->searchPaginated($query, $limit);
    }

    public function getAvailableForSale()
    {
        return $this->vehicleRepository->getAvailableForSale();
    }
}
