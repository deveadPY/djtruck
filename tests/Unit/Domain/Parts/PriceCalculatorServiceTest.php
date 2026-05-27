<?php

use App\Domain\Parts\Services\PriceCalculatorService;

beforeEach(fn() => $this->svc = new PriceCalculatorService());

it('calculates costo promedio ponderado', function () {
    // 10 unidades @ 100 + 10 unidades @ 200 = 200/2 = 150
    expect($this->svc->calcularCostoPromedio(10, 100, 10, 200))->toBe(150.0);
});

it('returns 0 when no stock', function () {
    expect($this->svc->calcularCostoPromedio(0, 0, 0, 0))->toBe(0.0);
});

it('sugiere precio venta con margen', function () {
    // costo 100, margen 30% → 130
    expect($this->svc->sugerirPrecioVenta(100, 30))->toBe(130.0);
});

it('calcula margen pct', function () {
    expect($this->svc->margenPct(100, 130))->toBe(30.0);
    expect($this->svc->margenPct(100, 100))->toBe(0.0);
});

it('returns 0 margen when costo zero', function () {
    expect($this->svc->margenPct(0, 100))->toBe(0.0);
});
