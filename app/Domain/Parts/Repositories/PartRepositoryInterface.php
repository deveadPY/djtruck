<?php

declare(strict_types=1);

namespace App\Domain\Parts\Repositories;

use App\Domain\Parts\Aggregates\Part;

interface PartRepositoryInterface
{
    public function save(Part $part): Part;

    public function update(int $id, Part $part): Part;

    public function findById(int $id): ?Part;

    public function findByCodigo(string $codigo): ?Part;

    public function findByCodigoBarras(string $codigoBarras): ?Part;

    public function existsByCodigo(string $codigo, ?int $excludeId = null): bool;

    public function existsByCodigoBarras(string $codigoBarras, ?int $excludeId = null): bool;
}
