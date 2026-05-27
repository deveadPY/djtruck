<?php

declare(strict_types=1);

namespace App\Application\Parts;

final class AdjustStockDTO
{
    public function __construct(
        public readonly int    $partId,
        public readonly float  $nuevaCantidad,
        public readonly string $motivo,      // MERMA | ROBO | DAÑO | AJUSTE_INVENTARIO | DEVOLUCION_CLIENTE | OTRO
        public readonly ?string $observaciones = null,
    ) {}
}
