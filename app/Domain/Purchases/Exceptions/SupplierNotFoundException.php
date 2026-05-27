<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Exceptions;

use RuntimeException;

class SupplierNotFoundException extends RuntimeException
{
    public readonly int $supplierId;

    public function __construct(int $supplierId)
    {
        $this->supplierId = $supplierId;
        parent::__construct("Proveedor con ID {$supplierId} no encontrado.");
    }
}
