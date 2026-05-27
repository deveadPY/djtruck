<?php

declare(strict_types=1);

use App\Domain\Sales\Validators\SaleIntegrityValidator;
use App\Domain\Shared\Exceptions\SaleAmountMismatchException;
use App\Domain\Shared\Exceptions\VehicleNotFoundException;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

beforeEach(function () {
    $this->vehicleRepo = Mockery::mock(VehicleRepositoryInterface::class);
    $this->validator = new SaleIntegrityValidator($this->vehicleRepo);
});

afterEach(function () {
    Mockery::close();
});

test('rechaza venta sin items', function () {
    $this->validator->validate([], 0);
})->throws(SaleAmountMismatchException::class, 'al menos un item');

test('valida que precio total coincida con suma de items', function () {
    $vehicle = new VehicleModel();
    $vehicle->estado = 'DISPONIBLE';
    $vehicle->marca = 'Toyota';
    $vehicle->modelo = 'Hilux';

    $this->vehicleRepo->shouldReceive('findById')->with(1)->andReturn($vehicle);

    $items = [
        ['itemable_id' => 1, 'itemable_type' => 'App\Models\Vehicle', 'cantidad' => 1, 'precio_unitario_usd' => 15000],
    ];

    expect(fn() => $this->validator->validate($items, 15000.0))->not->toThrow(Exception::class);
});

test('rechaza cuando suma de items difiere del precio total', function () {
    $vehicle = new VehicleModel();
    $vehicle->estado = 'DISPONIBLE';
    $vehicle->marca = 'Toyota';
    $vehicle->modelo = 'Hilux';

    $this->vehicleRepo->shouldReceive('findById')->with(1)->andReturn($vehicle);

    $items = [
        ['itemable_id' => 1, 'itemable_type' => 'App\Models\Vehicle', 'cantidad' => 1, 'precio_unitario_usd' => 15000],
    ];

    $this->validator->validate($items, 20000.0);
})->throws(SaleAmountMismatchException::class, 'no coincide');

test('rechaza cuando vehículo no existe', function () {
    $this->vehicleRepo->shouldReceive('findById')->with(99)->andReturn(null);

    $items = [
        ['itemable_id' => 99, 'itemable_type' => 'App\Models\Vehicle', 'cantidad' => 1, 'precio_unitario_usd' => 15000],
    ];

    $this->validator->validate($items, 15000.0);
})->throws(VehicleNotFoundException::class, 'no existe');

test('rechaza cuando vehículo está vendido', function () {
    $vehicle = new VehicleModel();
    $vehicle->estado = 'VENDIDO';
    $vehicle->marca = 'Toyota';
    $vehicle->modelo = 'Hilux';

    $this->vehicleRepo->shouldReceive('findById')->with(1)->andReturn($vehicle);

    $items = [
        ['itemable_id' => 1, 'itemable_type' => 'App\Models\Vehicle', 'cantidad' => 1, 'precio_unitario_usd' => 15000],
    ];

    $this->validator->validate($items, 15000.0);
})->throws(VehicleNotFoundException::class, 'no está disponible');

test('permite vehículo en estado RESERVADO', function () {
    $vehicle = new VehicleModel();
    $vehicle->estado = 'RESERVADO';
    $vehicle->marca = 'Toyota';
    $vehicle->modelo = 'Hilux';

    $this->vehicleRepo->shouldReceive('findById')->with(1)->andReturn($vehicle);

    $items = [
        ['itemable_id' => 1, 'itemable_type' => 'App\Models\Vehicle', 'cantidad' => 1, 'precio_unitario_usd' => 15000],
    ];

    expect(fn() => $this->validator->validate($items, 15000.0))->not->toThrow(Exception::class);
});

test('tolerancia de redondeo en validación de precio', function () {
    $vehicle = new VehicleModel();
    $vehicle->estado = 'DISPONIBLE';
    $vehicle->marca = 'Toyota';
    $vehicle->modelo = 'Hilux';

    $this->vehicleRepo->shouldReceive('findById')->with(1)->andReturn($vehicle);

    $items = [
        ['itemable_id' => 1, 'itemable_type' => 'App\Models\Vehicle', 'cantidad' => 1, 'precio_unitario_usd' => 15000.005],
    ];

    expect(fn() => $this->validator->validate($items, 15000.0))->not->toThrow(Exception::class);
});
