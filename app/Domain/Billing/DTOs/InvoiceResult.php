<?php

declare(strict_types=1);

namespace App\Domain\Billing\DTOs;

/**
 * Respuesta normalizada del proveedor.
 */
final class InvoiceResult
{
    public function __construct(
        public readonly bool    $success,
        public readonly string  $estado,           // PENDIENTE | APROBADO | RECHAZADO | CANCELADO | ERROR
        public readonly ?string $cdc = null,       // Código de Control identificador único Paraguay
        public readonly ?string $numero = null,
        public readonly ?string $urlPdf = null,
        public readonly ?string $urlXml = null,
        public readonly ?string $qrCode = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array   $rawResponse = [], // payload completo del provider para auditoría
    ) {}

    public static function fromException(\Throwable $e): self
    {
        return new self(
            success:      false,
            estado:       'ERROR',
            errorCode:    (string) $e->getCode(),
            errorMessage: $e->getMessage(),
        );
    }
}
