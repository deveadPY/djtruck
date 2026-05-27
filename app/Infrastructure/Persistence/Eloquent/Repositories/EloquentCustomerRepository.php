<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Customers\Aggregates\Customer;
use App\Domain\Customers\Repositories\CustomerRepositoryInterface;
use App\Domain\Customers\ValueObjects\CustomerId;
use App\Domain\Customers\ValueObjects\Ruc;
use App\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use Illuminate\Support\Facades\Auth;

final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function save(Customer $customer): Customer
    {
        $data = $customer->toArray();
        unset($data['id']);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $model = ClienteModel::create($data);

        return $customer->withId(CustomerId::fromInt($model->id));
    }

    public function update(int $id, Customer $customer): Customer
    {
        $data = $customer->toArray();
        unset($data['id']);
        $data['updated_by'] = Auth::id();

        ClienteModel::where('id', $id)->update($data);

        return $customer->withId(CustomerId::fromInt($id));
    }

    public function findById(int $id): ?Customer
    {
        $model = ClienteModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByRuc(string $ruc): ?Customer
    {
        $model = ClienteModel::where('ruc', $ruc)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(string $email): ?Customer
    {
        $model = ClienteModel::where('email', $email)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function existsByRuc(string $ruc, ?int $excludeId = null): bool
    {
        $q = ClienteModel::where('ruc', $ruc);
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->exists();
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $q = ClienteModel::where('email', $email);
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->exists();
    }

    private function toDomain(ClienteModel $model): Customer
    {
        $customer = Customer::create(
            ruc:             Ruc::parse($model->ruc),
            razonSocial:     $model->razon_social,
            nombreFantasia:  $model->nombre_fantasia,
            pais:            $model->pais ?? 'PY',
            email:           $model->email,
            telefono:        $model->telefono,
            direccion:       $model->direccion,
            lineaCreditoUsd: (float) $model->linea_credito_usd,
            activo:          (bool) $model->activo,
        );

        return $customer->withId(CustomerId::fromInt($model->id));
    }
}
