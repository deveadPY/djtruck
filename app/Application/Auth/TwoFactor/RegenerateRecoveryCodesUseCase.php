<?php

declare(strict_types=1);

namespace App\Application\Auth\TwoFactor;

use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class RegenerateRecoveryCodesUseCase
{
    public function __construct(
        private readonly TwoFactorService $service,
    ) {}

    /**
     * @return array<string> Los nuevos recovery codes
     */
    public function execute(User $user, string $password): array
    {
        if (!Hash::check($password, $user->password)) {
            throw new RuntimeException('Contraseña actual incorrecta.');
        }
        return $this->service->regenerateRecoveryCodes($user);
    }
}
