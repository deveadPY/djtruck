<?php

declare(strict_types=1);

namespace App\Domain\Leads\Events;

class LeadConverted
{
    public function __construct(
        public readonly int $leadId,
        public readonly int $ventaId,
        public readonly ?int $vendedorId,
    ) {}
}
