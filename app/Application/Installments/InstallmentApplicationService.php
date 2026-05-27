<?php

declare(strict_types=1);

namespace App\Application\Installments;

use App\Domain\Sales\Repositories\InstallmentRepositoryInterface;
use Illuminate\Support\Collection;

class InstallmentApplicationService
{
    public function __construct(
        private readonly PayInstallmentsUseCase $payInstallmentsUseCase,
        private readonly LiquidatePlanUseCase $liquidatePlanUseCase,
        private readonly InstallmentRepositoryInterface $installmentRepository
    ) {}

    public function pay(PayInstallmentsDTO $dto): array
    {
        return $this->payInstallmentsUseCase->execute($dto);
    }

    public function liquidate(LiquidatePlanDTO $dto): array
    {
        return $this->liquidatePlanUseCase->execute($dto);
    }

    public function findById(int $id): ?object
    {
        return $this->installmentRepository->findById($id);
    }

    public function getByPlan(int $planId): Collection
    {
        return $this->installmentRepository->getByPlan($planId);
    }

    public function getByVenta(int $ventaId): Collection
    {
        return $this->installmentRepository->getByVenta($ventaId);
    }

    public function getOverdue(): Collection
    {
        return $this->installmentRepository->getOverdue();
    }

    public function getDueToday(): Collection
    {
        return $this->installmentRepository->getDueToday();
    }
}
