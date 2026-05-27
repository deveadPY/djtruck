<?php

declare(strict_types=1);

namespace App\Domain\Billing\DTOs;

/**
 * Payload genérico para solicitar emisión de factura electrónica.
 * Cada adapter lo transforma al formato específico del proveedor.
 */
final class InvoiceRequest
{
    /**
     * @param array<int, array{
     *     descripcion: string,
     *     cantidad: float,
     *     precio_unitario: float,
     *     subtotal: float,
     *     iva_pct?: float
     * }> $items
     */
    public function __construct(
        public readonly int     $ventaId,
        public readonly string  $tipoDocumento,        // FACTURA | NOTA_CREDITO | NOTA_DEBITO | AUTOFACTURA
        public readonly string  $numeroDocumento,      // Ej: 001-001-0000123
        public readonly string  $fechaEmision,         // ISO 8601
        public readonly string  $monedaCodigo,         // PYG | USD | BRL
        public readonly float   $tasaCambio,           // 1 si moneda == PYG
        public readonly float   $totalNeto,
        public readonly float   $totalIva,
        public readonly float   $totalGeneral,
        public readonly array   $cliente,              // {ruc, razon_social, email, direccion, ...}
        public readonly array   $items,
        public readonly array   $pagos = [],           // forma de pago para info al SET
        public readonly ?string $observaciones = null,
    ) {}
}
