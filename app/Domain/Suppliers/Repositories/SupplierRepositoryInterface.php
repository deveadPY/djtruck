<?php

declare(strict_types=1);

namespace App\Domain\Suppliers\Repositories;

use App\Domain\Suppliers\Aggregates\Supplier;

interface SupplierRepositoryInterface
{
    public function save(Supplier $supplier): Supplier;
    public function update(int $id, Supplier $supplier): Supplier;
    public function findById(int $id): ?Supplier;
    public function findByRuc(string $ruc): ?Supplier;
    public function existsByRuc(string $ruc, ?int $excludeId = null): bool;
}
