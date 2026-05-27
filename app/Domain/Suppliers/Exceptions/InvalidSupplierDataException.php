<?php

declare(strict_types=1);

namespace App\Domain\Suppliers\Exceptions;

use RuntimeException;

class InvalidSupplierDataException extends RuntimeException
{
    public static function missingRazonSocial(): self
    {
        return new self('La razón social del proveedor es obligatoria.');
    }

    public static function invalidTipo(string $tipo): self
    {
        return new self("Tipo de proveedor inválido: '{$tipo}'. Valores válidos: FABRICANTE, DISTRIBUIDOR, IMPORTADOR, SERVICIO, OTRO.");
    }

    public static function invalidDescuentoAnticipado(float $pct): self
    {
        return new self("El descuento por pago anticipado ({$pct}%) debe estar entre 0 y 100.");
    }

    public static function invalidDiasCredito(int $dias): self
    {
        return new self("Los días de crédito ({$dias}) deben ser entre 0 y 365.");
    }
}
