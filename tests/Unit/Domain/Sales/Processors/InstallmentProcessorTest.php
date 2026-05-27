<?php

declare(strict_types=1);

use App\Domain\Sales\Processors\InstallmentProcessor;
use App\Domain\Sales\Services\InstallmentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->generator = Mockery::mock(InstallmentGenerator::class);
    $this->processor = new InstallmentProcessor($this->generator);
});

afterEach(function () {
    Mockery::close();
});

test('crea plan de cuotas manuales correctamente', function () {
    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 12000,
        'estado' => 'EN_PROCESO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cuotasManual = [
        ['monto' => 1000, 'fecha' => '2026-06-01'],
        ['monto' => 1000, 'fecha' => '2026-07-01'],
    ];

    $planId = $this->processor->process(
        ventaId: 1,
        clienteId: 1,
        monedaVenta: 'USD',
        tipoPlan: 'MANUAL',
        capitalTotalUsd: 2000.0,
        numeroCuotas: 2,
        tasaInteresMensual: 0,
        fechaPrimeraCuota: '2026-06-01',
        cuotasManual: $cuotasManual,
        refuerzoCada: 0,
        refuerzoMonto: 0,
        fechaVenta: '2026-05-01'
    );

    expect($planId)->toBeInt();
    expect(DB::table('planes_cuotas')->where('id', $planId)->exists())->toBeTrue();
    expect(DB::table('cuotas')->where('plan_cuotas_id', $planId)->count())->toBe(2);
});

test('plan MANUAL ignora cuotas con monto cero', function () {
    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 12000,
        'estado' => 'EN_PROCESO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cuotasManual = [
        ['monto' => 1000, 'fecha' => '2026-06-01'],
        ['monto' => 0, 'fecha' => '2026-07-01'],
        ['monto' => 500, 'fecha' => '2026-08-01'],
    ];

    $planId = $this->processor->process(
        ventaId: 1,
        clienteId: 1,
        monedaVenta: 'USD',
        tipoPlan: 'MANUAL',
        capitalTotalUsd: 1500.0,
        numeroCuotas: 3,
        tasaInteresMensual: 0,
        fechaPrimeraCuota: '2026-06-01',
        cuotasManual: $cuotasManual,
        refuerzoCada: 0,
        refuerzoMonto: 0,
        fechaVenta: '2026-05-01'
    );

    expect(DB::table('cuotas')->where('plan_cuotas_id', $planId)->count())->toBe(2);
});

test('revert elimina plan y cuotas asociadas', function () {
    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 12000,
        'estado' => 'EN_PROCESO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cuotasManual = [
        ['monto' => 1000, 'fecha' => '2026-06-01'],
        ['monto' => 1000, 'fecha' => '2026-07-01'],
    ];

    $this->processor->process(
        ventaId: 1,
        clienteId: 1,
        monedaVenta: 'USD',
        tipoPlan: 'MANUAL',
        capitalTotalUsd: 2000.0,
        numeroCuotas: 2,
        tasaInteresMensual: 0,
        fechaPrimeraCuota: '2026-06-01',
        cuotasManual: $cuotasManual,
        refuerzoCada: 0,
        refuerzoMonto: 0,
        fechaVenta: '2026-05-01'
    );

    $this->processor->revert(1);

    expect(DB::table('planes_cuotas')->where('venta_id', 1)->count())->toBe(0);
    expect(DB::table('cuotas')->count())->toBe(0);
});
