<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Observers;

final class SaleObserver extends BaseAuditObserver
{
    protected function entityType(): string
    {
        return 'venta';
    }
}
