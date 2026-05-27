<?php

declare(strict_types=1);

namespace App\Domain\Sales\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Support\Collection;

interface SaleRepositoryInterface
{
    public function findById(int $id): ?SaleModel;

    public function create(array $data): SaleModel;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function getLatest(int $limit = 20);

    public function addItems(int $saleId, array $items): void;

    public function getItems(int $saleId): Collection;

    public function removeItems(int $saleId): void;

    public function addPayment(int $saleId, array $paymentDetail): int;

    public function getPayments(int $saleId): Collection;

    public function removePayments(int $saleId): void;

    public function addInstallmentPlan(int $saleId, array $planData): int;

    public function getPlan(int $saleId): ?object;

    public function removePlan(int $saleId): void;

    public function getDocuments(int $saleId): Collection;
}
