<?php

declare(strict_types=1);

namespace App\Domain\Parts\Validators;

use App\Domain\Parts\Exceptions\DuplicatePartCodeException;
use App\Domain\Parts\Repositories\PartRepositoryInterface;

final class UniquePartCodeValidator
{
    public function __construct(
        private readonly PartRepositoryInterface $repository
    ) {}

    public function validate(string $codigo, ?int $excludeId = null): void
    {
        if ($this->repository->existsByCodigo($codigo, $excludeId)) {
            throw DuplicatePartCodeException::forCode($codigo);
        }
    }
}
