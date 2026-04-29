<?php

declare(strict_types=1);

namespace App\Application\Sales\DTOs;

final readonly class PayInstallmentData
{
    public function __construct(
        public int     $cuotaId,
        public float   $montoPagado,
        public string  $fechaPago,
        public int     $userId,
        public ?int    $cajaId      = null, // null → CajaCapital por defecto
        public ?string $observacion = null,
    ) {}
}
