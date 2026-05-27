<?php

declare(strict_types=1);

namespace App\Application\Sales;

final readonly class CancelSaleDTO
{
    public function __construct(
        public int     $id,
        public ?string $motivo = null,
    ) {}
}
