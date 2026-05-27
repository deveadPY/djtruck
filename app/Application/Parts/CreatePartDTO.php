<?php

declare(strict_types=1);

namespace App\Application\Parts;

final class CreatePartDTO
{
    public function __construct(
        public readonly string  $codigo,
        public readonly string  $descripcion,
        public readonly string  $unidadMedida = 'UND',
        public readonly ?string $codigoBarras = null,
        public readonly ?string $marcaCompatible = null,
        public readonly ?int    $categoriaId = null,
        public readonly ?int    $ubicacionId = null,
        public readonly float   $stockInicial = 0,
        public readonly float   $stockMinimo = 0,
        public readonly float   $costoPromedioUsd = 0,
        public readonly ?float  $precioVentaUsd = null,
        public readonly ?int    $proveedorId = null,
        public readonly bool    $activo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            codigo:           (string) ($data['codigo'] ?? ''),
            descripcion:      (string) ($data['descripcion'] ?? ''),
            unidadMedida:     (string) ($data['unidad_medida'] ?? 'UND'),
            codigoBarras:     $data['codigo_barras'] ?? null,
            marcaCompatible:  $data['marca_compatible'] ?? null,
            categoriaId:      isset($data['categoria_id']) ? (int) $data['categoria_id'] : null,
            ubicacionId:      isset($data['ubicacion_id']) ? (int) $data['ubicacion_id'] : null,
            stockInicial:     (float) ($data['stock_inicial'] ?? 0),
            stockMinimo:      (float) ($data['stock_minimo'] ?? 0),
            costoPromedioUsd: (float) ($data['costo_promedio_usd'] ?? 0),
            precioVentaUsd:   isset($data['precio_venta_usd']) ? (float) $data['precio_venta_usd'] : null,
            proveedorId:      isset($data['proveedor_id']) ? (int) $data['proveedor_id'] : null,
            activo:           (bool) ($data['activo'] ?? true),
        );
    }
}
