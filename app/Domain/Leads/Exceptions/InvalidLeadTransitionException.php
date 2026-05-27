<?php

declare(strict_types=1);

namespace App\Domain\Leads\Exceptions;

use App\Domain\Leads\ValueObjects\LeadStatus;
use RuntimeException;

class InvalidLeadTransitionException extends RuntimeException
{
    public static function notAllowed(LeadStatus $actual, LeadStatus $nuevo): self
    {
        return new self(
            "Transición no permitida: el lead está en '{$actual->value}' y no puede pasar a '{$nuevo->value}'."
        );
    }

    public static function alreadyTerminal(LeadStatus $actual): self
    {
        return new self(
            "El lead está en estado terminal '{$actual->value}' y no puede modificarse."
        );
    }
}
