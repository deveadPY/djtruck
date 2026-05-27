<?php

declare(strict_types=1);

namespace App\Domain\Quotes\ValueObjects;

enum QuoteStatus: string
{
    case BORRADOR    = 'BORRADOR';
    case ENVIADO     = 'ENVIADO';
    case ACEPTADO    = 'ACEPTADO';
    case RECHAZADO   = 'RECHAZADO';
    case VENCIDO     = 'VENCIDO';
    case CONVERTIDO  = 'CONVERTIDO';

    public function puedeTransicionarA(QuoteStatus $nuevo): bool
    {
        return match ($this) {
            self::BORRADOR   => in_array($nuevo, [self::ENVIADO, self::ACEPTADO, self::RECHAZADO], true),
            self::ENVIADO    => in_array($nuevo, [self::ACEPTADO, self::RECHAZADO, self::VENCIDO], true),
            self::ACEPTADO   => in_array($nuevo, [self::CONVERTIDO, self::VENCIDO], true),
            self::RECHAZADO,
            self::VENCIDO,
            self::CONVERTIDO => false,
        };
    }
}
