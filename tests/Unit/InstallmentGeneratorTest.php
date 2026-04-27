<?php

declare(strict_types=1);

use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\Exceptions\InvalidInstallmentPlanException;

beforeEach(function () {
    $this->generator = new InstallmentGenerator();
});

// ── Sistema Francés ──────────────────────────────────────────────────────

test('sistema francés genera cuota fija correctamente', function () {
    $capital = new Money(10000.0, Currency::USD);
    $result  = $this->generator->simulate(10000.0, 'USD', 12, 1.5, InstallmentPlan::FRANCESA, '2025-02-01');

    expect($result['cuotas'])->toHaveCount(12);

    // Cuota fija: todas deben ser casi iguales (excepto redondeo en última)
    $primera = $result['cuotas'][0]['total'];
    for ($i = 0; $i < 11; $i++) {
        expect($result['cuotas'][$i]['total'])->toBeBetween($primera - 0.02, $primera + 0.02);
    }
});

test('sistema francés sin interés divide capital de forma uniforme', function () {
    $result = $this->generator->simulate(6000.0, 'USD', 6, 0.0, InstallmentPlan::FRANCESA, '2025-01-01');

    expect($result['cuotas'])->toHaveCount(6);
    foreach ($result['cuotas'] as $cuota) {
        expect($cuota['capital'])->toBe(1000.0);
        expect($cuota['interes'])->toBe(0.0);
    }
});

test('sistema francés el capital total reconstruye el monto original', function () {
    $monto  = 15000.0;
    $result = $this->generator->simulate($monto, 'USD', 24, 2.0, InstallmentPlan::FRANCESA, '2025-01-01');

    $capitalTotal = collect($result['cuotas'])->sum('capital');
    expect($capitalTotal)->toBeBetween($monto - 0.10, $monto + 0.10);
});

// ── Sistema Alemán ───────────────────────────────────────────────────────

test('sistema alemán capital es fijo en todas las cuotas', function () {
    $monto  = 12000.0;
    $n      = 12;
    $result = $this->generator->simulate($monto, 'USD', $n, 1.0, InstallmentPlan::ALEMANA, '2025-01-01');

    $capFijo = round($monto / $n, 2);
    // Las primeras 11 cuotas deben tener capital fijo
    for ($i = 0; $i < $n - 1; $i++) {
        expect($result['cuotas'][$i]['capital'])->toBeBetween($capFijo - 0.01, $capFijo + 0.01);
    }
});

test('sistema alemán interés es decreciente', function () {
    $result = $this->generator->simulate(10000.0, 'USD', 10, 1.5, InstallmentPlan::ALEMANA, '2025-01-01');

    for ($i = 1; $i < count($result['cuotas']); $i++) {
        expect($result['cuotas'][$i]['interes'])->toBeLessThanOrEqual($result['cuotas'][$i - 1]['interes']);
    }
});

test('sistema alemán la suma de capital es igual al monto original', function () {
    $monto  = 9000.0;
    $result = $this->generator->simulate($monto, 'USD', 9, 1.2, InstallmentPlan::ALEMANA, '2025-01-01');

    $total = collect($result['cuotas'])->sum('capital');
    expect($total)->toBeBetween($monto - 0.10, $monto + 0.10);
});

// ── Resumen del simulate ─────────────────────────────────────────────────

test('simulate retorna resumen con capital_total, interes_total y costo_total', function () {
    $result = $this->generator->simulate(5000.0, 'USD', 5, 1.0, InstallmentPlan::FRANCESA, '2025-01-01');

    expect($result)->toHaveKeys(['cuotas', 'resumen']);
    expect($result['resumen'])->toHaveKeys(['capital_total', 'interes_total', 'costo_total', 'moneda']);
    expect($result['resumen']['costo_total'])->toEqual(
        round($result['resumen']['capital_total'] + $result['resumen']['interes_total'], 2)
    );
});

test('simulate con moneda PYG usa 0 decimales', function () {
    $result = $this->generator->simulate(7_800_000.0, 'PYG', 6, 1.5, InstallmentPlan::FRANCESA, '2025-01-01');

    expect($result['cuotas'])->toHaveCount(6);
    // PYG no tiene decimales
    foreach ($result['cuotas'] as $cuota) {
        expect(fmod($cuota['capital'], 1))->toBe(0.0);
    }
});

// ── Validaciones ─────────────────────────────────────────────────────────

test('lanza excepción con 0 cuotas', function () {
    $this->generator->simulate(5000.0, 'USD', 0, 1.0, InstallmentPlan::FRANCESA, '2025-01-01');
})->throws(InvalidInstallmentPlanException::class);

test('lanza excepción con más de 60 cuotas', function () {
    $this->generator->simulate(5000.0, 'USD', 61, 1.0, InstallmentPlan::FRANCESA, '2025-01-01');
})->throws(InvalidInstallmentPlanException::class);

test('lanza excepción con plan MANUAL en simulate', function () {
    $this->generator->simulate(5000.0, 'USD', 6, 1.0, InstallmentPlan::MANUAL, '2025-01-01');
})->throws(InvalidInstallmentPlanException::class);

// ── Fechas de vencimiento ────────────────────────────────────────────────

test('las fechas de vencimiento son mensuales consecutivas', function () {
    $result = $this->generator->simulate(3000.0, 'USD', 3, 1.0, InstallmentPlan::FRANCESA, '2025-03-01');

    expect($result['cuotas'][0]['vencimiento'])->toBe('2025-03-01');
    expect($result['cuotas'][1]['vencimiento'])->toBe('2025-04-01');
    expect($result['cuotas'][2]['vencimiento'])->toBe('2025-05-01');
});
