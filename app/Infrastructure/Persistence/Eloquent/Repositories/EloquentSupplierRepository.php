<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Suppliers\Aggregates\Supplier;
use App\Domain\Suppliers\Repositories\SupplierRepositoryInterface;
use App\Domain\Suppliers\ValueObjects\SupplierId;
use App\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Illuminate\Support\Facades\Auth;

final class EloquentSupplierRepository implements SupplierRepositoryInterface
{
    public function save(Supplier $supplier): Supplier
    {
        $data = $supplier->toArray();
        unset($data['id']);
        $data['created_by'] = Auth::id();

        $model = SupplierModel::create($data);
        return $supplier->withId(SupplierId::fromInt($model->id));
    }

    public function update(int $id, Supplier $supplier): Supplier
    {
        $data = $supplier->toArray();
        unset($data['id']);
        $data['updated_by'] = Auth::id();

        SupplierModel::where('id', $id)->update($data);
        return $supplier->withId(SupplierId::fromInt($id));
    }

    public function findById(int $id): ?Supplier
    {
        $model = SupplierModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByRuc(string $ruc): ?Supplier
    {
        $model = SupplierModel::where('ruc_rut_nit', $ruc)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function existsByRuc(string $ruc, ?int $excludeId = null): bool
    {
        $q = SupplierModel::where('ruc_rut_nit', $ruc);
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->exists();
    }

    private function toDomain(SupplierModel $model): Supplier
    {
        $supplier = Supplier::create(
            rucRutNit:                  $model->ruc_rut_nit,
            razonSocial:                $model->razon_social,
            nombreFantasia:             $model->nombre_fantasia,
            pais:                       $model->pais,
            tipo:                       $model->tipo,
            monedaPrincipal:            $model->moneda_principal,
            diasCredito:                (int) ($model->dias_credito ?? 0),
            descuentoPagoAnticipadoPct: (float) ($model->descuento_pago_anticipado_pct ?? 0),
            email:                      $model->email,
            telefono:                   $model->telefono,
            direccion:                  $model->direccion ?? null,
            ciudad:                     $model->ciudad ?? null,
            sitioWeb:                   $model->sitio_web ?? null,
            contactoPrincipal:          $model->contacto_principal ?? null,
            banco:                      $model->banco ?? null,
            cuentaBancaria:             $model->cuenta_bancaria ?? null,
            scoreActual:                (float) ($model->score_actual ?? 0),
            observaciones:              $model->observaciones ?? null,
            activo:                     (bool) $model->activo,
        );

        return $supplier->withId(SupplierId::fromInt($model->id));
    }
}
