<?php

declare(strict_types=1);

namespace App\Application\Auth\TwoFactor;

use App\Domain\Auth\TwoFactor\Exceptions\TwoFactorException;
use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use App\Models\User;

/**
 * Verifica un código 2FA (TOTP o recovery code).
 * Marca la sesión actual como "2FA verificada" mediante un flag en session.
 */
final class VerifyTwoFactorUseCase
{
    public const SESSION_KEY = 'two_factor_verified_at';

    public function __construct(
        private readonly TwoFactorService $service,
    ) {}

    public function execute(User $user, string $code): void
    {
        $code = trim($code);

        // Si parece TOTP (6 dígitos puros), intentar TOTP. Si no, recovery code.
        $isTotp = preg_match('/^\d{6}$/', $code) === 1;

        $ok = $isTotp
            ? $this->service->verifyCode($user, $code)
            : $this->service->useRecoveryCode($user, $code);

        if (!$ok) {
            throw TwoFactorException::invalidCode();
        }

        session([self::SESSION_KEY => now()->toIso8601String()]);
    }

    public static function isSessionVerified(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public static function forgetSession(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
