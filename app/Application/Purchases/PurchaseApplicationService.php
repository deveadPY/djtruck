<?php

declare(strict_types=1);

namespace App\Application\Purchases;

use App\Domain\Purchases\Repositories\PurchaseRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseApplicationService
{
    public function __construct(
        private readonly CreatePurchaseUseCase $createPurchaseUseCase,
        private readonly UpdatePurchaseUseCase $updatePurchaseUseCase,
        private readonly CancelPurchaseUseCase $cancelPurchaseUseCase,
        private readonly PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function create(CreatePurchaseDTO $dto): PurchaseModel
    {
        return $this->createPurchaseUseCase->execute($dto);
    }

    public function update(UpdatePurchaseDTO $dto): bool
    {
        return $this->updatePurchaseUseCase->execute($dto);
    }

    public function cancel(int $id): bool
    {
        return $this->cancelPurchaseUseCase->execute($id);
    }

    public function findById(int $id): ?PurchaseModel
    {
        return $this->purchaseRepository->findById($id);
    }

    public function search(?string $query, int $limit = 25): LengthAwarePaginator
    {
        return $this->purchaseRepository->searchPaginated($query, $limit);
    }
}
