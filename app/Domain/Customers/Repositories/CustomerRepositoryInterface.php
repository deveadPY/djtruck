<?php

declare(strict_types=1);

namespace App\Domain\Customers\Repositories;

use App\Domain\Customers\Aggregates\Customer;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): Customer;

    public function update(int $id, Customer $customer): Customer;

    public function findById(int $id): ?Customer;

    public function findByRuc(string $ruc): ?Customer;

    public function findByEmail(string $email): ?Customer;

    public function existsByRuc(string $ruc, ?int $excludeId = null): bool;

    public function existsByEmail(string $email, ?int $excludeId = null): bool;
}
