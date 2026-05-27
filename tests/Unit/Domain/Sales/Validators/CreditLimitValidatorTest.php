<?php

declare(strict_types=1);

use App\Domain\Sales\Validators\CreditLimitValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->validator = new CreditLimitValidator();
});

test('permite venta cuando cliente no tiene línea de crédito configurada', function () {
    DB::table('clientes')->insert([
        'id' => 1,
        'razon_social' => 'Test Cliente',
        'linea_credito_usd' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn() => $this->validator->validate(1, 10000.0))->not->toThrow(Exception::class);
});

test('permite venta cuando capital requerido es menor que crédito disponible', function () {
    DB::table('clientes')->insert([
        'id' => 1,
        'razon_social' => 'Test Cliente',
        'linea_credito_usd' => 50000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn() => $this->validator->validate(1, 20000.0))->not->toThrow(Exception::class);
});

test('rechaza venta cuando capital requerido supera línea de crédito', function () {
    DB::table('clientes')->insert([
        'id' => 1,
        'razon_social' => 'Test Cliente',
        'linea_credito_usd' => 10000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->validator->validate(1, 20000.0);
})->throws(RuntimeException::class, 'supera la línea de crédito');

test('rechaza venta cuando cliente no existe', function () {
    $this->validator->validate(999, 5000.0);
})->throws(RuntimeException::class, 'no encontrado');

test('getAvailableCredit retorna línea de crédito completa si no hay deuda', function () {
    DB::table('clientes')->insert([
        'id' => 1,
        'razon_social' => 'Test Cliente',
        'linea_credito_usd' => 30000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $disponible = $this->validator->getAvailableCredit(1, 30000);

    expect($disponible)->toBe(30000.0);
});
