<?php

declare(strict_types=1);

namespace App\Domain\Billing\DTOs;

final class CancelInvoiceResult
{
    public function __construct(
        public readonly bool    $success,
        public readonly string  $estado,             // CANCELADO | RECHAZADO_CANCELACION | ERROR
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array   $rawResponse = [],
    ) {}
}
