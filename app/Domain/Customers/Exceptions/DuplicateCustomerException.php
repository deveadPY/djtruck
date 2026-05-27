<?php

declare(strict_types=1);

namespace App\Domain\Customers\Exceptions;

use RuntimeException;

class DuplicateCustomerException extends RuntimeException
{
    public static function byRuc(string $ruc): self
    {
        return new self("Ya existe un cliente registrado con el RUC/CI: {$ruc}");
    }

    public static function byEmail(string $email): self
    {
        return new self("Ya existe un cliente registrado con el email: {$email}");
    }
}
