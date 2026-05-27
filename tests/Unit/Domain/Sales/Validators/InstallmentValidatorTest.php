<?php

declare(strict_types=1);

use App\Domain\Sales\Validators\InstallmentValidator;
use App\Domain\Shared\Exceptions\InvalidInstallmentPlanException;

beforeEach(function () {
    $this->validator = new InstallmentValidator();
});

test('valida plan FRANCESA con 12 cuotas', function () {
    expect(fn() => $this->validator->validate('FRANCESA', 12))->not->toThrow(Exception::class);
});

test('valida plan ALEMANA con cuotas válidas', function () {
    expect(fn() => $this->validator->validate('ALEMANA', 24))->not->toThrow(Exception::class);
});

test('rechaza tipo de plan inválido', function () {
    $this->validator->validate('INVALIDO', 12);
})->throws(InvalidInstallmentPlanException::class, 'Tipo de plan inválido');

test('rechaza menos de 1 cuota', function () {
    $this->validator->validate('FRANCESA', 0);
})->throws(InvalidInstallmentPlanException::class, 'número de cuotas debe estar entre');

test('rechaza más de 60 cuotas', function () {
    $this->validator->validate('FRANCESA', 61);
})->throws(InvalidInstallmentPlanException::class, 'número de cuotas debe estar entre');

test('acepta máximo de 60 cuotas', function () {
    expect(fn() => $this->validator->validate('FRANCESA', 60))->not->toThrow(Exception::class);
});

test('plan MANUAL requiere cuotas definidas', function () {
    $this->validator->validate('MANUAL', 12, []);
})->throws(InvalidInstallmentPlanException::class, 'requiere al menos una cuota');

test('plan MANUAL rechaza cuota con monto cero', function () {
    $cuotasManual = [
        ['monto' => 1000, 'fecha' => '2026-06-01'],
        ['monto' => 0, 'fecha' => '2026-07-01'],
    ];

    $this->validator->validate('MANUAL', 12, $cuotasManual);
})->throws(InvalidInstallmentPlanException::class, 'monto inválido');

test('plan MANUAL acepta cuotas válidas', function () {
    $cuotasManual = [
        ['monto' => 1000, 'fecha' => '2026-06-01'],
        ['monto' => 1000, 'fecha' => '2026-07-01'],
    ];

    expect(fn() => $this->validator->validate('MANUAL', 2, $cuotasManual))->not->toThrow(Exception::class);
});

test('rechaza fecha primera cuota anterior a fecha de venta', function () {
    $this->validator->validateFirstInstallmentDate('2026-05-01', '2026-06-01');
})->throws(InvalidInstallmentPlanException::class, 'no puede ser anterior');

test('acepta fecha primera cuota posterior a fecha de venta', function () {
    expect(fn() => $this->validator->validateFirstInstallmentDate('2026-07-01', '2026-06-01'))
        ->not->toThrow(Exception::class);
});

test('acepta fecha primera cuota null', function () {
    expect(fn() => $this->validator->validateFirstInstallmentDate(null, '2026-06-01'))
        ->not->toThrow(Exception::class);
});
