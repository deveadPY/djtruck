<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Parts\Aggregates\Part;
use App\Domain\Parts\Repositories\PartRepositoryInterface;
use App\Domain\Parts\ValueObjects\PartCode;
use App\Domain\Parts\ValueObjects\PartId;
use App\Infrastructure\Persistence\Eloquent\Models\RepuestoModel;
use Illuminate\Support\Facades\Auth;

final class EloquentPartRepository implements PartRepositoryInterface
{
    public function save(Part $part): Part
    {
        $data = $part->toArray();
        unset($data['id']);
        $data['created_by'] = Auth::id();

        $model = RepuestoModel::create($data);

        return $part->withId(PartId::fromInt($model->id));
    }

    public function update(int $id, Part $part): Part
    {
        $data = $part->toArray();
        unset($data['id']);
        $data['updated_by'] = Auth::id();

        RepuestoModel::where('id', $id)->update($data);

        return $part->withId(PartId::fromInt($id));
    }

    public function findById(int $id): ?Part
    {
        $model = RepuestoModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByCodigo(string $codigo): ?Part
    {
        $model = RepuestoModel::where('codigo', strtoupper($codigo))->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByCodigoBarras(string $codigoBarras): ?Part
    {
        $model = RepuestoModel::where('codigo_barras', $codigoBarras)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function existsByCodigo(string $codigo, ?int $excludeId = null): bool
    {
        $q = RepuestoModel::where('codigo', strtoupper($codigo));
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->exists();
    }

    public function existsByCodigoBarras(string $codigoBarras, ?int $excludeId = null): bool
    {
        if ($codigoBarras === '') {
            return false;
        }
        $q = RepuestoModel::where('codigo_barras', $codigoBarras);
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->exists();
    }

    private function toDomain(RepuestoModel $model): Part
    {
        $part = Part::create(
            codigo:           PartCode::parse($model->codigo),
            descripcion:      $model->descripcion,
            unidadMedida:     $model->unidad_medida,
            codigoBarras:     $model->codigo_barras ?? null,
            marcaCompatible:  $model->marca_compatible,
            categoriaId:      $model->categoria_id ?? null,
            ubicacionId:      $model->ubicacion_id ?? null,
            stockInicial:     (float) $model->stock_actual,
            stockMinimo:      (float) $model->stock_minimo,
            costoPromedioUsd: (float) $model->costo_promedio_usd,
            precioVentaUsd:   $model->precio_venta_usd !== null ? (float) $model->precio_venta_usd : null,
            proveedorId:      $model->proveedor_id,
            activo:           (bool) $model->activo,
        );

        return $part->withId(PartId::fromInt($model->id));
    }
}
