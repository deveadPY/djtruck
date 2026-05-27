<?php

declare(strict_types=1);

namespace App\Domain\Commissions\ValueObjects;

enum CommissionStatus: string
{
    case CALCULADA = 'CALCULADA';
    case APROBADA  = 'APROBADA';
    case PAGADA    = 'PAGADA';
    case ANULADA   = 'ANULADA';

    public function puedeTransicionarA(CommissionStatus $nuevo): bool
    {
        return match ($this) {
            self::CALCULADA => in_array($nuevo, [self::APROBADA, self::ANULADA], true),
            self::APROBADA  => in_array($nuevo, [self::PAGADA, self::ANULADA], true),
            self::PAGADA,
            self::ANULADA   => false,
        };
    }
}
