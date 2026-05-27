<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use App\Models\User;

class ClientePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('clientes.view') || $user->hasRole('vendedor');
    }

    public function view(User $user, ClienteModel $cliente): bool
    {
        return $user->can('clientes.view') || $user->hasRole('vendedor');
    }

    public function create(User $user): bool
    {
        return $user->can('clientes.create') || $user->hasRole('vendedor');
    }

    public function update(User $user, ClienteModel $cliente): bool
    {
        return $user->can('clientes.update');
    }

    public function delete(User $user, ClienteModel $cliente): bool
    {
        return $user->can('clientes.delete');
    }

    /**
     * Solo gerentes y admins pueden modificar el límite de crédito.
     */
    public function updateCreditLimit(User $user, ClienteModel $cliente): bool
    {
        return $user->can('clientes.credit.update') || $user->hasRole('gerente');
    }
}
