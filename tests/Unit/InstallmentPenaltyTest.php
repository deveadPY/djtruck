<?php

declare(strict_types=1);

use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;

beforeEach(function () {
    $this->generator = new InstallmentGenerator();
});

// ── Mora y penalidades ────────────────────────────────────────────────────

test('plan francés genera más costo total que capital (hay interés)', function () {
    $capital = 10000.0;
    $result  = $this->generator->simulate($capital, 'USD', 12, 2.0, InstallmentPlan::FRANCESA, '2026-01-01');

    $costoTotal = $result['resumen']['costo_total'];

    expect($costoTotal)->toBeGreaterThan($capital);
});

test('plan francés sin tasa tiene costo igual al capital', function () {
    $capital = 6000.0;
    $result  = $this->generator->simulate($capital, 'USD', 6, 0.0, InstallmentPlan::FRANCESA, '2026-01-01');

    expect($result['resumen']['interes_total'])->toBe(0.0)
        ->and($result['resumen']['costo_total'])->toBe($capital);
});

test('plan alemán cuota total decrece con el tiempo', function () {
    $result = $this->generator->simulate(12000.0, 'USD', 12, 1.5, InstallmentPlan::ALEMANA, '2026-01-01');

    $cuotas = $result['cuotas'];
    for ($i = 1; $i < count($cuotas); $i++) {
        expect($cuotas[$i]['total'])->toBeLessThanOrEqual($cuotas[$i - 1]['total']);
    }
});

test('tasa máxima del 10% mensual no genera plan inválido', function () {
    $result = $this->generator->simulate(5000.0, 'USD', 12, 10.0, InstallmentPlan::FRANCESA, '2026-01-01');

    expect($result['cuotas'])->toHaveCount(12);
    expect($result['resumen']['costo_total'])->toBeGreaterThan(5000.0);
});

test('plan con 60 cuotas (máximo) funciona correctamente', function () {
    $result = $this->generator->simulate(30000.0, 'USD', 60, 1.0, InstallmentPlan::FRANCESA, '2026-01-01');

    expect($result['cuotas'])->toHaveCount(60);

    $capitalTotal = collect($result['cuotas'])->sum('capital');
    expect($capitalTotal)->toBeBetween(29999.0, 30001.0);
});

test('primera cuota vence en la fecha indicada', function () {
    $fecha  = '2026-06-15';
    $result = $this->generator->simulate(5000.0, 'USD', 3, 1.0, InstallmentPlan::FRANCESA, $fecha);

    expect($result['cuotas'][0]['vencimiento'])->toBe($fecha);
});

test('la suma de capital en plan alemán iguala el capital original', function () {
    $capital = 9600.0;
    $result  = $this->generator->simulate($capital, 'USD', 12, 1.5, InstallmentPlan::ALEMANA, '2026-01-01');

    $sumaCapital = collect($result['cuotas'])->sum('capital');

    expect($sumaCapital)->toBeBetween($capital - 0.05, $capital + 0.05);
});
