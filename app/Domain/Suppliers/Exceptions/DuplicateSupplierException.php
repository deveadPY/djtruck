<?php

declare(strict_types=1);

namespace App\Domain\Suppliers\Exceptions;

use RuntimeException;

class DuplicateSupplierException extends RuntimeException
{
    public static function byRuc(string $ruc): self
    {
        return new self("Ya existe un proveedor registrado con el RUC/RUT/NIT: {$ruc}");
    }
}
