<?php

declare(strict_types=1);

namespace App\Application\Parts;

final class UpdatePartDTO
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $descripcion,
        public readonly string  $unidadMedida,
        public readonly ?string $codigoBarras = null,
        public readonly ?string $marcaCompatible = null,
        public readonly ?int    $categoriaId = null,
        public readonly ?int    $ubicacionId = null,
        public readonly float   $stockMinimo = 0,
        public readonly ?float  $precioVentaUsd = null,
        public readonly ?int    $proveedorId = null,
        public readonly bool    $activo = true,
    ) {}
}
