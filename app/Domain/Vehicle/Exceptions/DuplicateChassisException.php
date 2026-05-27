<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Exceptions;

use RuntimeException;

class DuplicateChassisException extends RuntimeException
{
    public readonly string $chassis;

    public function __construct(string $chassis)
    {
        $this->chassis = $chassis;
        parent::__construct(
            "Ya existe un vehículo con número de chasis '{$chassis}' en el sistema."
        );
    }
}
