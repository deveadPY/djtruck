<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VehicleRepositoryInterface
{
    public function findById(int $id): ?VehicleModel;
    
    public function create(array $data): VehicleModel;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id, int $userId = null): bool;
    
    public function searchPaginated(?string $searchQuery, int $limit = 15): LengthAwarePaginator;
    
    public function getAvailableForSale();
}
