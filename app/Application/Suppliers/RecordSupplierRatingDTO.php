<?php

declare(strict_types=1);

namespace App\Application\Suppliers;

final class RecordSupplierRatingDTO
{
    public function __construct(
        public readonly int     $supplierId,
        public readonly string  $criterio,        // CALIDAD_PRODUCTO | TIEMPO_ENTREGA | ...
        public readonly int     $puntaje,         // 1-5
        public readonly ?string $comentario = null,
        public readonly ?int    $compraId = null,
    ) {}
}
