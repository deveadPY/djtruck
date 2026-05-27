<?php

declare(strict_types=1);

use App\Application\Sales\CreateSaleDTO;
use App\Application\Sales\CreateSaleUseCase;
use App\Domain\Sales\Exceptions\InsufficientCreditLimitException;
use App\Domain\Sales\Exceptions\InvalidVehicleStateException;
use App\Domain\Sales\Exceptions\SalePriceInconsistencyException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::table('clientes')->insert([
        'id' => 1,
        'razon_social' => 'Cliente Test',
        'ruc' => '1234567-8',
        'linea_credito_usd' => 100000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('vehiculos')->insert([
        'id' => 1,
        'marca' => 'Toyota',
        'modelo' => 'Hilux',
        'numero_chasis' => 'TEST123',
        'anio' => 2024,
        'estado' => 'DISPONIBLE',
        'costo_origen_usd' => 12000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->useCase = app(CreateSaleUseCase::class);
});

function buildDto(array $overrides = []): CreateSaleDTO
{
    $defaults = [
        'clienteId' => 1,
        'fechaVenta' => '2026-05-01',
        'monedaVenta' => 'USD',
        'precioVentaMoneda' => 15000.0,
        'precioVentaUsd' => 15000.0,
        'modalidadPago' => 'CONTADO',
        'estado' => 'COMPLETADO',
        'tasaCambioVenta' => 1.0,
        'descuentoMoneda' => 0.0,
        'descuentoUsd' => 0.0,
        'observaciones' => null,
        'items' => [[
            'itemable_id' => 1,
            'itemable_type' => 'App\\Models\\Vehicle',
            'descripcion' => 'Toyota Hilux',
            'cantidad' => 1,
            'precio_unitario_usd' => 15000,
            'costo_snapshot_usd' => 12000,
        ]],
        'pagos' => [['tipo' => 'EFECTIVO', 'monto_usd' => 15000]],
        'vehiculoId' => null,
        'tipoPlan' => null,
        'capitalTotalUsd' => null,
        'numeroCuotas' => null,
        'tasaInteresMensual' => null,
        'fechaPrimeraCuota' => null,
        'cuotasManual' => [],
        'refuerzoCada' => null,
        'refuerzoMonto' => null,
    ];

    return new CreateSaleDTO(...array_merge($defaults, $overrides));
}

test('crea venta contado completa', function () {
    $dto = buildDto();

    $venta = $this->useCase->execute($dto);

    expect($venta->id)->toBeInt();
    expect(DB::table('venta_items')->where('venta_id', $venta->id)->count())->toBe(1);
    expect(DB::table('detalles_pago')->where('venta_id', $venta->id)->count())->toBe(1);
    expect(DB::table('vehiculos')->where('id', 1)->first()->estado)->toBe('VENDIDO');
});

test('rechaza venta cuando vehículo no está disponible', function () {
    DB::table('vehiculos')->where('id', 1)->update(['estado' => 'VENDIDO']);

    $this->useCase->execute(buildDto());
})->throws(InvalidVehicleStateException::class);

test('rechaza venta cuando suma de items no coincide con precio total', function () {
    $this->useCase->execute(buildDto([
        'precioVentaUsd' => 20000.0,
        'precioVentaMoneda' => 20000.0,
    ]));
})->throws(SalePriceInconsistencyException::class);

test('crea venta a cuotas con plan MANUAL', function () {
    $dto = buildDto([
        'modalidadPago' => 'CUOTAS',
        'estado' => 'EN_PROCESO',
        'pagos' => [['tipo' => 'EFECTIVO', 'monto_usd' => 5000]],
        'tipoPlan' => 'MANUAL',
        'capitalTotalUsd' => 10000.0,
        'numeroCuotas' => 2,
        'tasaInteresMensual' => 0.0,
        'fechaPrimeraCuota' => '2026-06-01',
        'cuotasManual' => [
            ['monto' => 5000, 'fecha' => '2026-06-01'],
            ['monto' => 5000, 'fecha' => '2026-07-01'],
        ],
    ]);

    $venta = $this->useCase->execute($dto);

    expect(DB::table('planes_cuotas')->where('venta_id', $venta->id)->count())->toBe(1);
    expect(DB::table('cuotas')->where('venta_id', $venta->id)->count())->toBe(2);
});

test('rechaza venta cuotas cuando supera línea de crédito', function () {
    DB::table('clientes')->where('id', 1)->update(['linea_credito_usd' => 5000]);

    $dto = buildDto([
        'modalidadPago' => 'CUOTAS',
        'estado' => 'EN_PROCESO',
        'pagos' => [],
        'tipoPlan' => 'MANUAL',
        'capitalTotalUsd' => 15000.0,
        'numeroCuotas' => 2,
        'fechaPrimeraCuota' => '2026-06-01',
        'cuotasManual' => [
            ['monto' => 7500, 'fecha' => '2026-06-01'],
            ['monto' => 7500, 'fecha' => '2026-07-01'],
        ],
    ]);

    $this->useCase->execute($dto);
})->throws(InsufficientCreditLimitException::class);
