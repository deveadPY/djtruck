<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Exceptions;

use RuntimeException;

class InvalidPurchaseDataException extends RuntimeException
{
    public static function noItems(): self
    {
        return new self('La compra debe contener al menos un item.');
    }

    public static function invalidQuantity(int $repuestoId, float $cantidad): self
    {
        return new self("Cantidad inválida para repuesto #{$repuestoId}: {$cantidad}. Debe ser mayor a cero.");
    }

    public static function invalidPrice(int $repuestoId, float $precio): self
    {
        return new self("Precio de compra inválido para repuesto #{$repuestoId}: USD {$precio}. Debe ser mayor a cero.");
    }

    public static function invalidExchangeRate(float $rate): self
    {
        return new self("Tasa de cambio inválida: {$rate}. Debe ser mayor a cero.");
    }

    public static function repuestoNotFound(int $repuestoId): self
    {
        return new self("Repuesto con ID {$repuestoId} no existe.");
    }
}
