<?php

declare(strict_types=1);

namespace App\Application\Installments;

final readonly class LiquidatePlanDTO
{
    public function __construct(
        public int    $planId,
        public string $fechaLiquidacion,
        public ?int   $cajaId,
        /** Si se debe aplicar descuento por liquidación */
        public bool   $aplicarDescuentoLiquidacion,
        /** Porcentaje de descuento sobre interés no devengado (ej. 20.0 = 20%) */
        public ?float $descuentoLiquidacionPct,
        public ?string $observaciones,
        public int    $userId,
        public ?string $ipAddress,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            planId:                      (int) $data['plan_id'],
            fechaLiquidacion:            $data['fecha_liquidacion'] ?? now()->toDateString(),
            cajaId:                      isset($data['caja_id']) ? (int) $data['caja_id'] : null,
            aplicarDescuentoLiquidacion: (bool) ($data['aplicar_descuento_liquidacion'] ?? false),
            descuentoLiquidacionPct:     isset($data['descuento_liquidacion_pct']) ? (float) $data['descuento_liquidacion_pct'] : null,
            observaciones:               $data['observaciones'] ?? null,
            userId:                      (int) ($data['user_id'] ?? 0),
            ipAddress:                   $data['ip_address'] ?? null,
        );
    }
}
