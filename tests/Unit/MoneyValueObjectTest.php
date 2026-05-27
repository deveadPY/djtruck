<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Currency;

// ── Creación ──────────────────────────────────────────────────────────────

test('Money se crea con monto y moneda correctamente', function () {
    $money = new Money(100.50, Currency::USD);

    expect($money->amount)->toBe(100.50)
        ->and($money->currency)->toBe(Currency::USD);
});

test('Money con monto negativo lanza excepción', function () {
    new Money(-1.0, Currency::USD);
})->throws(\InvalidArgumentException::class);

// ── Aritmética ────────────────────────────────────────────────────────────

test('add suma dos Money de la misma moneda', function () {
    $a = new Money(100.0, Currency::USD);
    $b = new Money(50.0, Currency::USD);

    $result = $a->add($b);

    expect($result->amount)->toBe(150.0)
        ->and($result->currency)->toBe(Currency::USD);
});

test('add con monedas distintas lanza excepción', function () {
    $a = new Money(100.0, Currency::USD);
    $b = new Money(50.0, Currency::PYG);

    $a->add($b);
})->throws(\InvalidArgumentException::class);

test('subtract resta correctamente', function () {
    $a = new Money(100.0, Currency::USD);
    $b = new Money(30.0, Currency::USD);

    $result = $a->subtract($b);

    expect($result->amount)->toBe(70.0);
});

test('subtract no puede resultar en negativo', function () {
    $a = new Money(10.0, Currency::USD);
    $b = new Money(50.0, Currency::USD);

    $a->subtract($b);
})->throws(\InvalidArgumentException::class);

test('multiply escala el monto correctamente', function () {
    $money  = new Money(100.0, Currency::USD);
    $result = $money->multiply(2.5);

    expect($result->amount)->toBe(250.0);
});

// ── PYG redondeo ──────────────────────────────────────────────────────────

test('PYG no tiene decimales al crear', function () {
    $money = new Money(7800.6, Currency::PYG);

    // PYG tiene 0 decimales — debe redondear
    expect(fmod($money->amount, 1))->toBe(0.0);
});

// ── Comparación ───────────────────────────────────────────────────────────

test('isGreaterThan compara correctamente', function () {
    $a = new Money(200.0, Currency::USD);
    $b = new Money(100.0, Currency::USD);

    expect($a->isGreaterThan($b))->toBeTrue()
        ->and($b->isGreaterThan($a))->toBeFalse();
});

test('equals compara monto y moneda', function () {
    $a = new Money(100.0, Currency::USD);
    $b = new Money(100.0, Currency::USD);
    $c = new Money(100.0, Currency::PYG);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
