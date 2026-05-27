<?php

declare(strict_types=1);

use App\Domain\Sales\Processors\SaleItemProcessor;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Domain\Sales\Services\ItemDescriptionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->processor = new SaleItemProcessor(
        app(SaleRepositoryInterface::class),
        app(ItemDescriptionResolver::class),
    );
});

test('procesa item de vehículo e inserta venta_items', function () {
    DB::table('vehiculos')->insert([
        'id' => 1,
        'marca' => 'Toyota',
        'modelo' => 'Hilux',
        'numero_chasis' => 'TEST123',
        'anio' => 2024,
        'estado' => 'DISPONIBLE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 15000,
        'estado' => 'COMPLETADO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $items = [[
        'itemable_id' => 1,
        'itemable_type' => 'App\\Models\\Vehicle',
        'descripcion' => 'Toyota Hilux 2024',
        'cantidad' => 1,
        'precio_unitario_usd' => 15000,
        'costo_snapshot_usd' => 12000,
    ]];

    $this->processor->process(1, $items, 1.0);

    expect(DB::table('venta_items')->count())->toBe(1);
    expect(DB::table('vehiculos')->where('id', 1)->first()->estado)->toBe('VENDIDO');
});

test('procesa item de repuesto y decrementa stock', function () {
    DB::table('stock_repuestos')->insert([
        'id' => 1,
        'descripcion' => 'Filtro de aceite',
        'stock_actual' => 10,
        'precio_venta_usd' => 50,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('ventas')->insert([
        'id' => 1,
        'cliente_id' => 1,
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 250,
        'estado' => 'COMPLETADO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $items = [[
        'itemable_id' => 1,
        'itemable_type' => 'App\\Infrastructure\\Persistence\\Eloquent\\Models\\RepuestoModel',
        'descripcion' => 'Filtro',
        'cantidad' => 3,
        'precio_unitario_usd' => 50,
        'costo_snapshot_usd' => 30,
    ]];

    $this->processor->process(1, $items, 1.0);

    expect(DB::table('stock_repuestos')->where('id', 1)->first()->stock_actual)->toBe(7);
});

test('revert devuelve vehículo a DISPONIBLE', function () {
    DB::table('vehiculos')->insert([
        'id' => 1,
        'marca' => 'Toyota',
        'modelo' => 'Hilux',
        'numero_chasis' => 'TEST123',
        'anio' => 2024,
        'estado' => 'VENDIDO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('venta_items')->insert([
        'venta_id' => 1,
        'itemable_id' => 1,
        'itemable_type' => 'App\\Models\\Vehicle',
        'descripcion' => 'Toyota',
        'cantidad' => 1,
        'precio_unitario_usd' => 15000,
        'precio_unitario_moneda' => 15000,
        'subtotal_usd' => 15000,
        'subtotal_moneda' => 15000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->processor->revert(1);

    expect(DB::table('vehiculos')->where('id', 1)->first()->estado)->toBe('DISPONIBLE');
    expect(DB::table('venta_items')->where('venta_id', 1)->count())->toBe(0);
});
