<?php

declare(strict_types=1);

namespace App\Application\Parts;

use App\Domain\Parts\Repositories\PartRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Actualiza datos editables del repuesto (sin cambiar stock — eso es AdjustStockUseCase).
 */
final class UpdatePartUseCase
{
    public function __construct(
        private readonly PartRepositoryInterface $repository,
    ) {}

    public function execute(UpdatePartDTO $dto): void
    {
        $part = $this->repository->findById($dto->id);
        if (!$part) {
            throw new RuntimeException("Repuesto {$dto->id} no encontrado.");
        }

        // Actualizar precio venta a través del Aggregate (valida ≥ costo)
        if ($dto->precioVentaUsd !== null) {
            $part->actualizarPrecioVenta($dto->precioVentaUsd);
        }

        if (!$dto->activo) {
            $part->desactivar();
        } else {
            $part->activar();
        }

        // Para atributos generales no críticos, actualización directa via repository
        DB::transaction(function () use ($dto) {
            DB::table('stock_repuestos')
                ->where('id', $dto->id)
                ->whereNull('deleted_at')
                ->update([
                    'descripcion'      => $dto->descripcion,
                    'unidad_medida'    => strtoupper($dto->unidadMedida),
                    'codigo_barras'    => $dto->codigoBarras,
                    'marca_compatible' => $dto->marcaCompatible,
                    'categoria_id'     => $dto->categoriaId,
                    'ubicacion_id'     => $dto->ubicacionId,
                    'stock_minimo'     => $dto->stockMinimo,
                    'precio_venta_usd' => $dto->precioVentaUsd,
                    'proveedor_id'     => $dto->proveedorId,
                    'activo'           => $dto->activo,
                    'updated_by'       => \Illuminate\Support\Facades\Auth::id(),
                    'updated_at'       => now(),
                ]);
        });
    }
}
