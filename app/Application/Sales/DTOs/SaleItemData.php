<?php

declare(strict_types=1);

namespace App\Application\Sales\DTOs;

final readonly class SaleItemData
{
    public function __construct(
        public string $itemableType,
        public int    $itemableId,
        public string $descripcion,
        public float  $cantidad,
        public float  $precioUnitarioUsd,
        public float  $costoSnapshotUsd,
    ) {}

    public static function fromArray(array $item): self
    {
        return new self(
            itemableType:      $item['itemable_type'],
            itemableId:        (int)   $item['itemable_id'],
            descripcion:       $item['descripcion'] ?? 'Item sin descripción',
            cantidad:          (float) $item['cantidad'],
            precioUnitarioUsd: (float) $item['precio_unitario_usd'],
            costoSnapshotUsd:  (float) ($item['costo_snapshot_usd'] ?? 0),
        );
    }
}
