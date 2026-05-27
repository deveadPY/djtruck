<?php

declare(strict_types=1);

namespace App\Domain\Auth\TwoFactor\ValueObjects;

/**
 * Recovery code para 2FA — formato xxxx-xxxx-xxxx (12 chars + 2 guiones).
 * 12 chars en base32 ≈ 60 bits de entropía → adecuado para fallback de un solo uso.
 */
final class RecoveryCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sin O/0, I/1 para legibilidad
    private const SEGMENT_LENGTH = 4;
    private const SEGMENTS = 3;

    public static function generate(): string
    {
        $segments = [];
        for ($s = 0; $s < self::SEGMENTS; $s++) {
            $seg = '';
            for ($i = 0; $i < self::SEGMENT_LENGTH; $i++) {
                $seg .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $segments[] = $seg;
        }
        return implode('-', $segments);
    }

    /**
     * Genera un set de recovery codes.
     */
    public static function generateSet(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = self::generate();
        }
        return $codes;
    }

    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }
}
