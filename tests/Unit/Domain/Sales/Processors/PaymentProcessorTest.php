<?php

declare(strict_types=1);

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Processors\PaymentProcessor;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cajaService = Mockery::mock(CajaService::class);
    $this->processor = new PaymentProcessor($this->cajaService);
});

afterEach(function () {
    Mockery::close();
});

test('procesa pago en efectivo e inserta detalle de pago', function () {
    $this->cajaService->shouldReceive('ingresoCapital')->once();

    $venta = new SaleModel();
    $venta->id = 1;
    $venta->numero_venta = 'V-202605-0001';
    $venta->fecha_venta = '2026-05-01';

    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'numero_venta' => 'V-202605-0001',
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 15000,
        'estado' => 'COMPLETADO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $pagos = [
        ['tipo' => 'EFECTIVO', 'monto_usd' => 5000],
    ];

    $total = $this->processor->process($venta, $pagos, 'CONTADO');

    expect($total)->toBe(5000.0);
    expect(DB::table('detalles_pago')->count())->toBe(1);
});

test('ignora pagos con monto cero o negativo', function () {
    $venta = new SaleModel();
    $venta->id = 1;
    $venta->numero_venta = 'V-202605-0001';
    $venta->fecha_venta = '2026-05-01';

    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'numero_venta' => 'V-202605-0001',
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 15000,
        'estado' => 'COMPLETADO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $pagos = [
        ['tipo' => 'EFECTIVO', 'monto_usd' => 0],
        ['tipo' => 'EFECTIVO', 'monto_usd' => -100],
    ];

    $total = $this->processor->process($venta, $pagos, 'CONTADO');

    expect($total)->toBe(0.0);
    expect(DB::table('detalles_pago')->count())->toBe(0);
});

test('marca vehículo como TOMA en pago de tipo VEHICULO_CANJE', function () {
    DB::table('vehiculos')->insert([
        'id' => 99,
        'marca' => 'Ford',
        'modelo' => 'F-150',
        'numero_chasis' => 'CANJE123',
        'anio' => 2020,
        'estado' => 'DISPONIBLE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $venta = new SaleModel();
    $venta->id = 1;
    $venta->numero_venta = 'V-202605-0001';
    $venta->fecha_venta = '2026-05-01';

    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'numero_venta' => 'V-202605-0001',
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 15000,
        'estado' => 'COMPLETADO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $pagos = [
        ['tipo' => 'VEHICULO_CANJE', 'monto_usd' => 8000, 'vehiculo_canje_id' => 99],
    ];

    $this->processor->process($venta, $pagos, 'CONTADO');

    expect(DB::table('vehiculos')->where('id', 99)->first()->estado)->toBe('TOMA');
});
