<?php

declare(strict_types=1);

namespace App\Domain\Leads\ValueObjects;

/**
 * Pipeline de un lead:
 *   nuevo → contactado → cerrado (= ganado, venta concretada)
 *                     → perdido
 */
enum LeadStatus: string
{
    case NUEVO       = 'nuevo';
    case CONTACTADO  = 'contactado';
    case CERRADO     = 'cerrado';
    case PERDIDO     = 'perdido';

    public function puedeTransicionarA(LeadStatus $nuevo): bool
    {
        return match ($this) {
            self::NUEVO       => in_array($nuevo, [self::CONTACTADO, self::CERRADO, self::PERDIDO], true),
            self::CONTACTADO  => in_array($nuevo, [self::CERRADO, self::PERDIDO], true),
            self::CERRADO,
            self::PERDIDO     => false, // estados terminales
        };
    }

    public function esTerminal(): bool
    {
        return $this === self::CERRADO || $this === self::PERDIDO;
    }
}
