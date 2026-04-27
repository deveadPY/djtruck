<?php

namespace App\Domain\Sales\Events;

class InstallmentOverdue
{
    public function __construct(
        public readonly int $installmentId,
        public readonly int $saleId,
        public readonly int $clienteId,
        public readonly int $diasMora,
    ) {}
}
