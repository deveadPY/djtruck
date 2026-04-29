<?php

declare(strict_types=1);

namespace App\Domain\Sales\ValueObjects;

enum InstallmentPlan: string
{
    case FRANCESA = 'FRANCESA';
    case ALEMANA  = 'ALEMANA';
    case MANUAL   = 'MANUAL';

    public function description(): string
    {
        return match ($this) {
            self::FRANCESA => 'Cuota fija (capital + interés constante)',
            self::ALEMANA  => 'Capital fijo + interés decreciente',
            self::MANUAL   => 'Montos definidos manualmente',
        };
    }
}
