<?php

declare(strict_types=1);

namespace App\Domain\Suppliers\Validators;

use App\Domain\Suppliers\Exceptions\DuplicateSupplierException;
use App\Domain\Suppliers\Repositories\SupplierRepositoryInterface;

final class UniqueSupplierRucValidator
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository
    ) {}

    public function validate(?string $ruc, ?int $excludeId = null): void
    {
        if (!$ruc) {
            return;
        }
        if ($this->repository->existsByRuc($ruc, $excludeId)) {
            throw DuplicateSupplierException::byRuc($ruc);
        }
    }
}
