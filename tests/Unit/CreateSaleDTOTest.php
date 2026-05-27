<?php

declare(strict_types=1);

use App\Application\Sales\CreateSaleDTO;

test('CreateSaleDTO almacena todos los campos correctamente', function () {
    $dto = new CreateSaleDTO(
        clienteId:          1,
        fechaVenta:         '2026-05-01',
        monedaVenta:        'USD',
        precioVentaMoneda:  15000.0,
        precioVentaUsd:     15000.0,
        modalidadPago:      'CONTADO',
        estado:             'COMPLETADO',
        tasaCambioVenta:    1.0,
        descuentoMoneda:    500.0,
        descuentoUsd:       500.0,
        observaciones:      'Test venta',
        items:              [['itemable_id' => 1, 'itemable_type' => 'vehiculo', 'cantidad' => 1, 'precio_unitario_usd' => 15000, 'costo_snapshot_usd' => 12000]],
        pagos:              [['tipo' => 'EFECTIVO', 'monto_usd' => 14500]],
        vehiculoId:         null,
        tipoPlan:           null,
        capitalTotalUsd:    null,
        numeroCuotas:       null,
        tasaInteresMensual: null,
        fechaPrimeraCuota:  null,
        cuotasManual:       [],
        refuerzoCada:       null,
        refuerzoMonto:      null,
    );

    expect($dto->clienteId)->toBe(1)
        ->and($dto->modalidadPago)->toBe('CONTADO')
        ->and($dto->descuentoUsd)->toBe(500.0)
        ->and($dto->items)->toHaveCount(1)
        ->and($dto->pagos)->toHaveCount(1);
});

test('CreateSaleDTO precio final neto es precio menos descuento', function () {
    $dto = new CreateSaleDTO(
        clienteId:          1,
        fechaVenta:         '2026-05-01',
        monedaVenta:        'USD',
        precioVentaMoneda:  20000.0,
        precioVentaUsd:     20000.0,
        modalidadPago:      'CONTADO',
        estado:             'COMPLETADO',
        tasaCambioVenta:    1.0,
        descuentoMoneda:    0.0,
        descuentoUsd:       1000.0,
        observaciones:      null,
        items:              [],
        pagos:              [],
        vehiculoId:         null,
        tipoPlan:           null,
        capitalTotalUsd:    null,
        numeroCuotas:       null,
        tasaInteresMensual: null,
        fechaPrimeraCuota:  null,
        cuotasManual:       [],
        refuerzoCada:       null,
        refuerzoMonto:      null,
    );

    $precioNeto = $dto->precioVentaUsd - $dto->descuentoUsd;

    expect($precioNeto)->toBe(19000.0);
});

test('CreateSaleDTO con plan cuotas almacena configuración del plan', function () {
    $dto = new CreateSaleDTO(
        clienteId:          2,
        fechaVenta:         '2026-05-01',
        monedaVenta:        'USD',
        precioVentaMoneda:  10000.0,
        precioVentaUsd:     10000.0,
        modalidadPago:      'CUOTAS',
        estado:             'EN_PROCESO',
        tasaCambioVenta:    1.0,
        descuentoMoneda:    0.0,
        descuentoUsd:       0.0,
        observaciones:      null,
        items:              [],
        pagos:              [],
        vehiculoId:         null,
        tipoPlan:           'FRANCESA',
        capitalTotalUsd:    8000.0,
        numeroCuotas:       12,
        tasaInteresMensual: 1.5,
        fechaPrimeraCuota:  '2026-06-01',
        cuotasManual:       [],
        refuerzoCada:       null,
        refuerzoMonto:      null,
    );

    expect($dto->modalidadPago)->toBe('CUOTAS')
        ->and($dto->tipoPlan)->toBe('FRANCESA')
        ->and($dto->numeroCuotas)->toBe(12)
        ->and($dto->tasaInteresMensual)->toBe(1.5)
        ->and($dto->capitalTotalUsd)->toBe(8000.0);
});
