<?php
// ============================================================
// app/Domain/Shared/ValueObjects/Currency.php
// ============================================================
namespace App\Domain\Shared\ValueObjects;

enum Currency: string
{
    case USD = 'USD';
    case PYG = 'PYG';
    case BRL = 'BRL';

    public function label(): string
    {
        return match ($this) {
            self::USD => 'Dólar Estadounidense',
            self::PYG => 'Guaraní Paraguayo',
            self::BRL => 'Real Brasileño',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::PYG => '₲',
            self::BRL => 'R$',
        };
    }

    public function decimals(): int
    {
        return match ($this) {
            self::PYG => 0,
            self::USD, self::BRL => 2,
        };
    }

    public function isBase(): bool { return $this === self::USD; }

    public static function nonBase(): array
    {
        return array_filter(self::cases(), fn(self $c) => !$c->isBase());
    }
}
