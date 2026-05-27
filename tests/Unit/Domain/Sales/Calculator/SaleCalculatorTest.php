<?php

declare(strict_types=1);

use App\Domain\Sales\Calculator\SaleCalculator;

beforeEach(function () {
    $this->calculator = new SaleCalculator();
});

test('calculateFinalPrice resta descuento del precio', function () {
    expect($this->calculator->calculateFinalPrice(15000, 1000))->toBe(14000.0);
});

test('calculateFinalPrice no permite precios negativos', function () {
    expect($this->calculator->calculateFinalPrice(1000, 5000))->toBe(0.0);
});

test('calculateBookValue suma costo_snapshot_usd * cantidad', function () {
    $items = [
        ['costo_snapshot_usd' => 5000, 'cantidad' => 2],
        ['costo_snapshot_usd' => 3000, 'cantidad' => 1],
    ];

    expect($this->calculator->calculateBookValue($items))->toBe(13000.0);
});

test('calculateBookValue retorna 0 con items vacíos', function () {
    expect($this->calculator->calculateBookValue([]))->toBe(0.0);
});

test('calculateGrossMargin calcula margen bruto', function () {
    expect($this->calculator->calculateGrossMargin(15000, 12000))->toBe(3000.0);
});

test('calculateMarginPercentage calcula porcentaje correctamente', function () {
    expect($this->calculator->calculateMarginPercentage(3000, 12000))->toBe(25.0);
});

test('calculateMarginPercentage retorna 0 si valor libro es 0', function () {
    expect($this->calculator->calculateMarginPercentage(3000, 0))->toBe(0.0);
});

test('calculateInitialPaymentTotal suma todos los pagos', function () {
    $pagos = [
        ['monto_usd' => 1000],
        ['monto_usd' => 2500],
        ['monto_usd' => 500],
    ];

    expect($this->calculator->calculateInitialPaymentTotal($pagos))->toBe(4000.0);
});

test('calculateInitialPaymentTotal retorna 0 sin pagos', function () {
    expect($this->calculator->calculateInitialPaymentTotal([]))->toBe(0.0);
});
