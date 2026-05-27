<?php

declare(strict_types=1);

namespace App\Domain\Auth\TwoFactor\Services;

use App\Domain\Auth\TwoFactor\Exceptions\TwoFactorException;
use App\Domain\Auth\TwoFactor\ValueObjects\RecoveryCode;
use App\Domain\Auth\TwoFactor\ValueObjects\Totp;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio que orquesta 2FA para un usuario.
 *
 * Almacenamiento:
 *  - users.two_factor_secret             → encrypted (Crypt::encryptString)
 *  - users.two_factor_recovery_codes     → encrypted JSON de codes (sin usar)
 *  - users.two_factor_confirmed_at       → timestamp cuando se activó
 *  - users.two_factor_required           → boolean (forzar 2FA por política)
 */
final class TwoFactorService
{
    public function __construct(private readonly string $issuer = 'DJ Trucks') {}

    /**
     * Inicia el proceso de setup: genera secret y recovery codes pero NO los confirma todavía.
     * El usuario debe escanear el QR e ingresar el primer código para confirmar.
     *
     * @return array{secret: string, recovery_codes: array<string>, qr_uri: string}
     */
    public function startSetup(User $user): array
    {
        // Si ya hay un setup pendiente (secret existe pero no confirmado), REUTILIZARLO.
        // Esto evita que recargas de /2fa/setup invaliden el QR que el usuario ya escaneó.
        if ($user->two_factor_secret && !$user->two_factor_confirmed_at) {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $codes  = $user->two_factor_recovery_codes
                ? (json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?? [])
                : RecoveryCode::generateSet(8);
        } else {
            $secret = Totp::generateSecret();
            $codes  = RecoveryCode::generateSet(8);

            $user->forceFill([
                'two_factor_secret'         => Crypt::encryptString($secret),
                'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
                'two_factor_confirmed_at'   => null,
            ])->save();
        }

        return [
            'secret'         => $secret,
            'recovery_codes' => $codes,
            'qr_uri'         => Totp::provisioningUri($secret, $user->email, $this->issuer),
        ];
    }

    /**
     * Confirma la activación verificando el primer código TOTP del usuario.
     * Usa ventana ±2 períodos (90s tolerancia) para tolerar clock drift entre
     * el celular del usuario y el servidor.
     */
    public function confirm(User $user, string $code): void
    {
        if (!$user->two_factor_secret) {
            throw TwoFactorException::notEnabled();
        }
        if ($user->two_factor_confirmed_at) {
            throw TwoFactorException::alreadyConfirmed();
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        if (!Totp::verify($secret, $code, window: 2)) {
            \Illuminate\Support\Facades\Log::warning('2fa.confirm.invalid', [
                'user_id'         => $user->id,
                'code_received'   => $code,
                'server_time'     => now()->toIso8601String(),
                'server_unix'     => time(),
                'expected_now'    => Totp::code($secret),
                'expected_prev'   => Totp::code($secret, time() - 30),
                'expected_next'   => Totp::code($secret, time() + 30),
            ]);
            throw TwoFactorException::invalidCode();
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->audit($user->id, '2FA_ACTIVATED');
    }

    /**
     * Verifica un código TOTP (durante login o re-autorización de acción sensible).
     * Ventana ±2 (90s) para clock drift.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->two_factor_secret || !$user->two_factor_confirmed_at) {
            return false;
        }
        $secret = Crypt::decryptString($user->two_factor_secret);
        return Totp::verify($secret, $code, window: 2);
    }

    /**
     * Consume un recovery code: lo elimina del set si es válido.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $normalized = RecoveryCode::normalize($code);
        $codes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?? [];

        $idx = array_search($normalized, array_map([RecoveryCode::class, 'normalize'], $codes), true);
        if ($idx === false) {
            return false;
        }

        unset($codes[$idx]);
        $codes = array_values($codes);

        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ])->save();

        $this->audit($user->id, '2FA_RECOVERY_CODE_USED', ['remaining' => count($codes)]);

        return true;
    }

    /**
     * Genera nuevos recovery codes (invalida los anteriores).
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        if (!$user->two_factor_confirmed_at) {
            throw TwoFactorException::notEnabled();
        }
        $codes = RecoveryCode::generateSet(8);
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ])->save();

        $this->audit($user->id, '2FA_RECOVERY_CODES_REGENERATED');

        return $codes;
    }

    /**
     * Desactiva 2FA para un usuario. Debe haberse verificado contraseña previamente.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        $this->audit($user->id, '2FA_DISABLED');
    }

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    public function getRecoveryCodes(User $user): array
    {
        if (!$user->two_factor_recovery_codes) {
            return [];
        }
        return json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?? [];
    }

    private function audit(int $userId, string $action, array $metadata = []): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => 'user',
                'entity_id'   => $userId,
                'old_values'  => null,
                'new_values'  => null,
                'metadata'    => json_encode($metadata) ?: null,
                'ip_address'  => request()?->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable) {
            // Auditoría nunca debe bloquear flujo principal
        }
    }
}
