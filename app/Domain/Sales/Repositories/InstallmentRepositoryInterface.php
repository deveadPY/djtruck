<?php

declare(strict_types=1);

namespace App\Domain\Sales\Repositories;

use Illuminate\Support\Collection;

interface InstallmentRepositoryInterface
{
    public function insertMany(array $cuotas): void;

    public function getByPlan(int $planId): Collection;

    public function getByVenta(int $ventaId): Collection;

    public function findById(int $id): ?object;

    public function update(int $id, array $data): bool;

    public function deleteByPlan(int $planId): void;

    public function getOverdue(): Collection;

    public function getDueToday(): Collection;
}
