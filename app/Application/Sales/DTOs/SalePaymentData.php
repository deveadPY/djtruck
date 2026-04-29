<?php

declare(strict_types=1);

namespace App\Application\Sales\DTOs;

final readonly class SalePaymentData
{
    public function __construct(
        public string  $tipo,
        public float   $montoUsd,
        public ?int    $vehiculoCanjeId,
        public ?string $referencia,
    ) {}

    public static function fromArray(array $pago): self
    {
        return new self(
            tipo:           $pago['tipo'] ?? 'EFECTIVO',
            montoUsd:       (float) ($pago['monto_usd'] ?? 0),
            vehiculoCanjeId: ($pago['tipo'] === 'VEHICULO_CANJE' && !empty($pago['vehiculo_canje_id']))
                                ? (int) $pago['vehiculo_canje_id'] : null,
            referencia:     $pago['referencia'] ?? null,
        );
    }
}
