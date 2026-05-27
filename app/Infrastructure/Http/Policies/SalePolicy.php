<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use App\Models\User;

class SalePolicy
{
    /**
     * Super-admin pasa todas las checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('sales.view') || $user->hasRole('vendedor');
    }

    public function view(User $user, SaleModel $sale): bool
    {
        if ($user->can('sales.view')) {
            return true;
        }
        return $user->hasRole('vendedor') && (int) $sale->vendedor_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create') || $user->hasRole('vendedor');
    }

    public function update(User $user, SaleModel $sale): bool
    {
        if (in_array($sale->estado, ['COMPLETADO', 'COMPLETADA'], true)) {
            return $user->can('sales.update.completed');
        }
        return $user->can('sales.update') || (
            $user->hasRole('vendedor') && (int) $sale->vendedor_id === (int) $user->id
        );
    }

    public function delete(User $user, SaleModel $sale): bool
    {
        return $user->can('sales.delete');
    }

    public function cancel(User $user, SaleModel $sale): bool
    {
        return $user->can('sales.cancel');
    }
}
