<?php

declare(strict_types=1);

namespace App\Domain\Auth\TwoFactor\ValueObjects;

/**
 * TOTP (RFC 6238) — Time-based One-Time Password.
 * Implementación nativa PHP. Compatible con Google Authenticator, Authy, Microsoft Authenticator.
 *
 * Parámetros estándar:
 *   - Algoritmo: HMAC-SHA1
 *   - Período: 30 segundos
 *   - Dígitos: 6
 */
final class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGORITHM = 'sha1';
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Genera un nuevo secret aleatorio en base32 (160 bits = 32 chars).
     */
    public static function generateSecret(int $length = 32): string
    {
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[ord($bytes[$i]) & 0x1F];
        }
        return $secret;
    }

    /**
     * Calcula el código TOTP actual para un secret dado.
     */
    public static function code(string $secretBase32, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = (int) floor($timestamp / self::PERIOD);
        return self::computeHotp($secretBase32, $counter);
    }

    /**
     * Verifica si un código es válido para el secret, con ventana de ±N períodos (clock drift).
     */
    public static function verify(string $secretBase32, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }

        $timestamp = $timestamp ?? time();
        $counter = (int) floor($timestamp / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            $expected = self::computeHotp($secretBase32, $counter + $i);
            // hash_equals para comparación constante-tiempo (anti timing attack)
            if (hash_equals($expected, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Genera URL otpauth:// para QR code (compatible con apps autenticadoras).
     *
     * Ejemplo: otpauth://totp/DJ%20Trucks:admin@example.com?secret=ABC123&issuer=DJ%20Trucks
     */
    public static function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);

        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * HOTP (RFC 4226) — base del TOTP. Counter en lugar de timestamp.
     */
    private static function computeHotp(string $secretBase32, int $counter): string
    {
        $secretBin = self::base32Decode($secretBase32);
        $counterBin = pack('N*', 0) . pack('N*', $counter);  // 8 bytes big-endian

        $hash = hash_hmac(self::ALGORITHM, $counterBin, $secretBin, true);

        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
                | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                |  (ord($hash[$offset + 3]) & 0xFF);

        $modulo = 10 ** self::DIGITS;
        return str_pad((string) ($binary % $modulo), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $input): string
    {
        $input = strtoupper(preg_replace('/[^A-Z2-7]/', '', $input));
        if ($input === '') {
            return '';
        }

        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        $alphabet = self::BASE32_ALPHABET;

        for ($i = 0, $n = strlen($input); $i < $n; $i++) {
            $val = strpos($alphabet, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }
}
