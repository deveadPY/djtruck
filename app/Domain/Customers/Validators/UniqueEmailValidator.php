<?php

declare(strict_types=1);

namespace App\Domain\Customers\Validators;

use App\Domain\Customers\Exceptions\DuplicateCustomerException;
use App\Domain\Customers\Repositories\CustomerRepositoryInterface;

final class UniqueEmailValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository
    ) {}

    public function validate(?string $email, ?int $excludeId = null): void
    {
        if (!$email) {
            return;
        }
        if ($this->repository->existsByEmail($email, $excludeId)) {
            throw DuplicateCustomerException::byEmail($email);
        }
    }
}
