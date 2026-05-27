<?php

declare(strict_types=1);

namespace App\Domain\Billing\Exceptions;

use RuntimeException;

class BillingProviderException extends RuntimeException
{
    public static function notConfigured(string $driver): self
    {
        return new self("El proveedor de facturación '{$driver}' no está configurado correctamente. Verifique config/billing.php y .env");
    }

    public static function apiError(string $provider, string $message, ?string $code = null): self
    {
        return new self("Error del proveedor {$provider}: {$message}" . ($code ? " (code: {$code})" : ''));
    }

    public static function networkError(string $provider): self
    {
        return new self("No se pudo conectar con el proveedor {$provider}. Verifique conectividad e intente nuevamente.");
    }

    public static function invalidResponse(string $provider): self
    {
        return new self("El proveedor {$provider} devolvió una respuesta inválida.");
    }
}
