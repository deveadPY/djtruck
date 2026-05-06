<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PurchaseRepositoryInterface
{
    public function findById(int $id): ?PurchaseModel;
    
    public function create(array $data): PurchaseModel;
    
    public function update(int $id, array $data): bool;
    
    public function searchPaginated(?string $searchQuery, int $limit = 25): LengthAwarePaginator;
}
