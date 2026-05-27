<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Observers;

final class ClienteObserver extends BaseAuditObserver
{
    protected function entityType(): string
    {
        return 'cliente';
    }
}
