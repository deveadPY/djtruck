<?php

declare(strict_types=1);

namespace App\Application\Warranties;

final class CreateClaimDTO
{
    public function __construct(
        public readonly int    $garantiaId,
        public readonly string $descripcionProblema,
        public readonly ?int   $tecnicoAsignadoId = null,
    ) {}
}
