<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel;
use App\Models\User;

class PurchasePolicy
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
        return $user->can('purchases.view') || $user->hasRole('compras');
    }

    public function view(User $user, PurchaseModel $purchase): bool
    {
        return $user->can('purchases.view') || $user->hasRole('compras');
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.create') || $user->hasRole('compras');
    }

    public function update(User $user, PurchaseModel $purchase): bool
    {
        if ($purchase->estado === 'PAGADA') {
            return $user->can('purchases.update.paid');
        }
        return $user->can('purchases.update');
    }

    public function delete(User $user, PurchaseModel $purchase): bool
    {
        return $user->can('purchases.delete');
    }

    public function cancel(User $user, PurchaseModel $purchase): bool
    {
        return $user->can('purchases.cancel');
    }
}
