<?php

declare(strict_types=1);

namespace App\Application\Installments;

final readonly class PayInstallmentsDTO
{
    public function __construct(
        /** @var int[] IDs de las cuotas a pagar */
        public array  $cuotasIds,
        /** Monto total que está pagando el cliente */
        public float  $montoPagado,
        /** Moneda del pago */
        public string $moneda,
        /** Fecha efectiva del pago */
        public string $fechaPago,
        /** Caja donde se registra el ingreso (null = Caja Capital por defecto) */
        public ?int   $cajaId,
        /** Si se debe aplicar descuento por pronto pago */
        public bool   $aplicarDescuentoAnticipo,
        /** Porcentaje de descuento por anticipo (ej. 5.0 = 5%). Solo si $aplicarDescuentoAnticipo es true */
        public ?float $descuentoAnticipoPct,
        /** Si el descuento por anticipo es proporcional a los días de anticipación */
        public bool   $descuentoProporcional,
        public ?string $observaciones,
        /** ID del usuario que realiza el cobro */
        public int    $userId,
        public ?string $ipAddress,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cuotasIds:                 array_map('intval', $data['cuotas_ids'] ?? []),
            montoPagado:               (float) ($data['monto_pagado'] ?? 0),
            moneda:                    $data['moneda'] ?? 'USD',
            fechaPago:                 $data['fecha_pago'] ?? now()->toDateString(),
            cajaId:                    isset($data['caja_id']) ? (int) $data['caja_id'] : null,
            aplicarDescuentoAnticipo:  (bool) ($data['aplicar_descuento_anticipo'] ?? false),
            descuentoAnticipoPct:      isset($data['descuento_anticipo_pct']) ? (float) $data['descuento_anticipo_pct'] : null,
            descuentoProporcional:     (bool) ($data['descuento_proporcional'] ?? false),
            observaciones:             $data['observaciones'] ?? null,
            userId:                    (int) ($data['user_id'] ?? 0),
            ipAddress:                 $data['ip_address'] ?? null,
        );
    }
}
