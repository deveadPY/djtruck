<?php

declare(strict_types=1);

namespace App\Domain\Auth\TwoFactor\Exceptions;

use RuntimeException;

class TwoFactorException extends RuntimeException
{
    public static function notEnabled(): self
    {
        return new self('Two-Factor Authentication no está activado para este usuario.');
    }

    public static function alreadyConfirmed(): self
    {
        return new self('Two-Factor Authentication ya fue confirmado.');
    }

    public static function invalidCode(): self
    {
        return new self('El código de autenticación es inválido o expiró.');
    }

    public static function invalidRecoveryCode(): self
    {
        return new self('El código de recuperación es inválido o ya fue usado.');
    }

    public static function requiredForRole(string $role): self
    {
        return new self("El rol '{$role}' requiere activar Two-Factor Authentication.");
    }
}
