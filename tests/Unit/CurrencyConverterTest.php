<?php

declare(strict_types=1);

use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Inyectar tasas de prueba en cache para no depender del BCN
    Cache::put('rate_PYG_USD', 1 / 7800, now()->addHour());
    Cache::put('rate_USD_PYG', 7800.0,   now()->addHour());
    Cache::put('rate_BRL_USD', 1 / 5.05, now()->addHour());
    Cache::put('rate_USD_BRL', 5.05,     now()->addHour());

    $this->converter = new CurrencyConverter();
});

// ── Misma moneda ─────────────────────────────────────────────────────────

test('convertir misma moneda devuelve el mismo monto', function () {
    $result = $this->converter->convert(100.0, Currency::USD, Currency::USD);

    expect($result)->toBeInstanceOf(Money::class);
    expect($result->amount)->toBe(100.0);
    expect($result->currency)->toBe(Currency::USD);
});

// ── USD ↔ PYG ────────────────────────────────────────────────────────────

test('convierte USD a PYG correctamente', function () {
    $result = $this->converter->convert(1.0, Currency::USD, Currency::PYG);

    expect($result->amount)->toBe(7800.0);
    expect($result->currency)->toBe(Currency::PYG);
});

test('convierte PYG a USD correctamente', function () {
    $result = $this->converter->convert(7800.0, Currency::PYG, Currency::USD);

    expect($result->amount)->toBe(1.0);
    expect($result->currency)->toBe(Currency::USD);
});

// ── Métodos helper ───────────────────────────────────────────────────────

test('toBaseCurrency convierte PYG a USD', function () {
    $result = $this->converter->toBaseCurrency(15600.0, Currency::PYG);

    expect($result->currency)->toBe(Currency::USD);
    expect($result->amount)->toBe(2.0);
});

test('fromBaseCurrency convierte USD a PYG', function () {
    $result = $this->converter->fromBaseCurrency(2.0, Currency::PYG);

    expect($result->currency)->toBe(Currency::PYG);
    expect($result->amount)->toBe(15600.0);
});

// ── Tasa manual ──────────────────────────────────────────────────────────

test('setManualRate sobreescribe la tasa del cache', function () {
    $this->converter->setManualRate(Currency::USD, Currency::PYG, 8000.0);
    $result = $this->converter->convert(1.0, Currency::USD, Currency::PYG);

    expect($result->amount)->toBe(8000.0);
});

test('setManualRate lanza excepción con tasa <= 0', function () {
    $this->converter->setManualRate(Currency::USD, Currency::PYG, -100.0);
})->throws(\App\Domain\Shared\Exceptions\CurrencyConversionException::class);

// ── Formato ───────────────────────────────────────────────────────────────

test('format USD incluye símbolo $', function () {
    $formatted = $this->converter->format(1234.56, Currency::USD);
    expect($formatted)->toContain('$');
    expect($formatted)->toContain('1,234.56');
});

test('format PYG incluye símbolo ₲ y sin decimales', function () {
    $formatted = $this->converter->format(7800.0, Currency::PYG);
    expect($formatted)->toContain('₲');
    expect($formatted)->not->toContain('.00');
});

test('format BRL incluye símbolo R$', function () {
    $formatted = $this->converter->format(5.05, Currency::BRL);
    expect($formatted)->toContain('R$');
});

// ── getAllCurrentRates ────────────────────────────────────────────────────

test('getAllCurrentRates retorna tasas para PYG y BRL', function () {
    $rates = $this->converter->getAllCurrentRates();

    expect($rates)->toHaveKey('PYG');
    expect($rates)->toHaveKey('BRL');
    expect($rates['PYG']['rate'])->toBeFloat();
    expect($rates['BRL']['rate'])->toBeFloat();
});
