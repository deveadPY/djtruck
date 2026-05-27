<?php

declare(strict_types=1);

namespace App\Domain\Warranties\Exceptions;

use RuntimeException;

class WarrantyException extends RuntimeException
{
    public static function notVigente(int $garantiaId, string $estado): self
    {
        return new self("La garantía {$garantiaId} no está vigente (estado: {$estado}). No se pueden registrar reclamos.");
    }

    public static function invalidDateRange(): self
    {
        return new self('La fecha de vencimiento debe ser posterior a la fecha de inicio.');
    }

    public static function expired(string $vencimiento): self
    {
        return new self("La garantía venció el {$vencimiento}.");
    }
}
