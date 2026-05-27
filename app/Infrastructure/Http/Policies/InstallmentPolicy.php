<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\InstallmentModel;
use App\Models\User;

class InstallmentPolicy
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
        return $user->can('installments.view') || $user->hasAnyRole(['cobrador', 'vendedor']);
    }

    public function view(User $user, InstallmentModel $cuota): bool
    {
        return $user->can('installments.view') || $user->hasAnyRole(['cobrador', 'vendedor']);
    }

    public function pay(User $user, InstallmentModel $cuota): bool
    {
        if (in_array($cuota->estado, ['PAGADA', 'CANCELADA'], true)) {
            return false;
        }
        return $user->can('installments.pay') || $user->hasRole('cobrador');
    }

    public function liquidate(User $user, InstallmentModel $cuota): bool
    {
        return $user->can('installments.liquidate') || $user->hasRole('gerente');
    }

    public function applyDiscount(User $user, InstallmentModel $cuota): bool
    {
        return $user->can('installments.discount') || $user->hasRole('gerente');
    }

    public function delete(User $user, InstallmentModel $cuota): bool
    {
        return $user->can('installments.delete');
    }
}
