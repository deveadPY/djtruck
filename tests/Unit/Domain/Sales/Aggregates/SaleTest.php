<?php

declare(strict_types=1);

use App\Domain\Sales\Aggregates\Payment;
use App\Domain\Sales\Aggregates\Sale;
use App\Domain\Sales\Aggregates\SaleId;
use App\Domain\Sales\Aggregates\SaleItem;
use App\Domain\Sales\Exceptions\SalePriceInconsistencyException;
use App\Domain\Sales\ValueObjects\PaymentType;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;

test('crea venta con datos básicos', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-202605-0001',
        precioTotal: Money::of(15000, 'USD'),
        descuento: Money::of(500, 'USD'),
        monedaVenta: Currency::USD,
        modalidadPago: 'CONTADO',
    );

    expect($sale->getNumeroVenta())->toBe('V-202605-0001')
        ->and($sale->getEstado())->toBe('EN_PROCESO')
        ->and($sale->getItems()->isEmpty())->toBeTrue();
});

test('precioFinal resta descuento del precio total', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-001',
        precioTotal: Money::of(15000, 'USD'),
        descuento: Money::of(500, 'USD'),
        monedaVenta: Currency::USD,
        modalidadPago: 'CONTADO',
    );

    expect($sale->precioFinal()->amount)->toBe(14500.0);
});

test('addItem suma al total de items', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-001',
        precioTotal: Money::of(15000, 'USD'),
        descuento: Money::zero(Currency::USD),
        monedaVenta: Currency::USD,
        modalidadPago: 'CONTADO',
    );

    $sale->addItem(new SaleItem(
        itemableId: 1,
        itemableType: 'App\\Models\\Vehicle',
        descripcion: 'Toyota Hilux',
        cantidad: 1,
        precioUnitario: Money::of(15000, 'USD'),
        costoSnapshot: Money::of(12000, 'USD'),
    ));

    expect($sale->totalItems()->amount)->toBe(15000.0)
        ->and($sale->totalBookValue()->amount)->toBe(12000.0)
        ->and($sale->margenBruto()->amount)->toBe(3000.0);
});

test('rechaza venta sin items en validateInvariants', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-001',
        precioTotal: Money::of(15000, 'USD'),
        descuento: Money::zero(Currency::USD),
        monedaVenta: Currency::USD,
        modalidadPago: 'CONTADO',
    );

    $sale->validateInvariants();
})->throws(SalePriceInconsistencyException::class);

test('rechaza venta donde suma de items no coincide con precio total', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-001',
        precioTotal: Money::of(20000, 'USD'),
        descuento: Money::zero(Currency::USD),
        monedaVenta: Currency::USD,
        modalidadPago: 'CONTADO',
    );

    $sale->addItem(new SaleItem(
        itemableId: 1,
        itemableType: 'App\\Models\\Vehicle',
        descripcion: 'Toyota Hilux',
        cantidad: 1,
        precioUnitario: Money::of(15000, 'USD'),
        costoSnapshot: Money::of(12000, 'USD'),
    ));

    $sale->validateInvariants();
})->throws(SalePriceInconsistencyException::class, 'no coincide');

test('venta CUOTAS sin plan asignado falla en validateInvariants', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-001',
        precioTotal: Money::of(15000, 'USD'),
        descuento: Money::zero(Currency::USD),
        monedaVenta: Currency::USD,
        modalidadPago: 'CUOTAS',
    );

    $sale->addItem(new SaleItem(
        itemableId: 1,
        itemableType: 'App\\Models\\Vehicle',
        descripcion: 'Toyota Hilux',
        cantidad: 1,
        precioUnitario: Money::of(15000, 'USD'),
        costoSnapshot: Money::of(12000, 'USD'),
    ));

    $sale->validateInvariants();
})->throws(SalePriceInconsistencyException::class);

test('venta CUOTAS con plan asignado pasa validateInvariants', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-001',
        precioTotal: Money::of(15000, 'USD'),
        descuento: Money::zero(Currency::USD),
        monedaVenta: Currency::USD,
        modalidadPago: 'CUOTAS',
    );

    $sale->addItem(new SaleItem(
        itemableId: 1,
        itemableType: 'App\\Models\\Vehicle',
        descripcion: 'Toyota Hilux',
        cantidad: 1,
        precioUnitario: Money::of(15000, 'USD'),
        costoSnapshot: Money::of(12000, 'USD'),
    ));

    $sale->assignInstallmentPlan(42);

    expect(fn() => $sale->validateInvariants())->not->toThrow(Exception::class);
});

test('addPayment acumula en totalPaid', function () {
    $sale = Sale::create(
        clienteId: 1,
        numeroVenta: 'V-001',
        precioTotal: Money::of(15000, 'USD'),
        descuento: Money::zero(Currency::USD),
        monedaVenta: Currency::USD,
        modalidadPago: 'CONTADO',
    );

    $sale->addPayment(new Payment(
        type: PaymentType::EFECTIVO,
        amount: Money::of(5000, 'USD'),
    ));

    $sale->addPayment(new Payment(
        type: PaymentType::TRANSFERENCIA,
        amount: Money::of(10000, 'USD'),
    ));

    expect($sale->totalPaid()->amount)->toBe(15000.0);
});

test('SaleId rechaza valores no positivos', function () {
    new SaleId(0);
})->throws(InvalidArgumentException::class);

test('SaleItem rechaza cantidad cero', function () {
    new SaleItem(
        itemableId: 1,
        itemableType: 'App\\Models\\Vehicle',
        descripcion: 'Test',
        cantidad: 0,
        precioUnitario: Money::of(1000, 'USD'),
        costoSnapshot: Money::of(500, 'USD'),
    );
})->throws(InvalidArgumentException::class);

test('Payment rechaza VEHICULO_CANJE sin tradeInVehicleId', function () {
    new Payment(
        type: PaymentType::VEHICULO_CANJE,
        amount: Money::of(5000, 'USD'),
    );
})->throws(InvalidArgumentException::class);

test('Payment rechaza PLAN_CUOTAS sin planCuotasId', function () {
    new Payment(
        type: PaymentType::PLAN_CUOTAS,
        amount: Money::of(5000, 'USD'),
    );
})->throws(InvalidArgumentException::class);
