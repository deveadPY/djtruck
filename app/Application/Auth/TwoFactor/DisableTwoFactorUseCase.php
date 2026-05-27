<?php

declare(strict_types=1);

namespace App\Application\Auth\TwoFactor;

use App\Domain\Auth\TwoFactor\Exceptions\TwoFactorException;
use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Desactiva 2FA. Requiere password confirmation por seguridad.
 * No permite desactivar si el usuario tiene rol que lo requiere obligatorio.
 */
final class DisableTwoFactorUseCase
{
    public function __construct(
        private readonly TwoFactorService $service,
    ) {}

    public function execute(User $user, string $password): void
    {
        if (!Hash::check($password, $user->password)) {
            throw new RuntimeException('Contraseña actual incorrecta.');
        }

        // Si el rol exige 2FA, bloquear desactivación
        if ($user->two_factor_required) {
            $rol = $user->getRoleNames()->first() ?? 'desconocido';
            throw TwoFactorException::requiredForRole($rol);
        }

        $this->service->disable($user);
    }
}
