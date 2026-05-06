<?php

declare(strict_types=1);

namespace App\Domain\Sales\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;

interface SaleRepositoryInterface
{
    public function findById(int $id): ?SaleModel;
    
    public function create(array $data): SaleModel;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function getLatest(int $limit = 20);
}
