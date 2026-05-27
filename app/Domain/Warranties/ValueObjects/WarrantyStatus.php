<?php

declare(strict_types=1);

namespace App\Domain\Warranties\ValueObjects;

enum WarrantyStatus: string
{
    case VIGENTE     = 'VIGENTE';
    case VENCIDA     = 'VENCIDA';
    case AGOTADA_KM  = 'AGOTADA_KM';
    case ANULADA     = 'ANULADA';

    public function permiteReclamo(): bool
    {
        return $this === self::VIGENTE;
    }
}
