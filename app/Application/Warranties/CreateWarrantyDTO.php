<?php

declare(strict_types=1);

namespace App\Application\Warranties;

final class CreateWarrantyDTO
{
    public function __construct(
        public readonly int     $ventaId,
        public readonly string  $inicio,        // YYYY-MM-DD
        public readonly string  $vencimiento,   // YYYY-MM-DD
        public readonly string  $tipo = 'FABRICA',
        public readonly ?int    $vehiculoId = null,
        public readonly ?int    $repuestoId = null,
        public readonly ?int    $kmInicio = null,
        public readonly ?int    $kmLimite = null,
        public readonly ?string $cobertura = null,
        public readonly ?string $exclusiones = null,
    ) {}
}
