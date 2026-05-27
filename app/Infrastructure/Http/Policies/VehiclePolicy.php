<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use App\Models\User;

class VehiclePolicy
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
        return $user->can('vehicles.view') || $user->hasAnyRole(['vendedor', 'mecanico']);
    }

    public function view(User $user, VehicleModel $vehicle): bool
    {
        return $user->can('vehicles.view') || $user->hasAnyRole(['vendedor', 'mecanico']);
    }

    public function create(User $user): bool
    {
        return $user->can('vehicles.create');
    }

    public function update(User $user, VehicleModel $vehicle): bool
    {
        if ($vehicle->estado === 'VENDIDO') {
            return $user->can('vehicles.update.sold');
        }
        return $user->can('vehicles.update');
    }

    public function delete(User $user, VehicleModel $vehicle): bool
    {
        if ($vehicle->estado === 'VENDIDO') {
            return false;
        }
        return $user->can('vehicles.delete');
    }

    public function addExpense(User $user, VehicleModel $vehicle): bool
    {
        return $user->can('vehicles.expenses.create') || $user->hasRole('mecanico');
    }
}
