<?php

declare(strict_types=1);

namespace App\Domain\Customers\Validators;

use App\Domain\Customers\Exceptions\DuplicateCustomerException;
use App\Domain\Customers\Repositories\CustomerRepositoryInterface;

final class UniqueRucValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository
    ) {}

    public function validate(string $ruc, ?int $excludeId = null): void
    {
        if ($this->repository->existsByRuc($ruc, $excludeId)) {
            throw DuplicateCustomerException::byRuc($ruc);
        }
    }
}
