<?php

use App\Domain\Suppliers\Aggregates\Supplier;
use App\Domain\Suppliers\Exceptions\InvalidSupplierDataException;

function makeSupplier(): Supplier
{
    return Supplier::create(
        rucRutNit:                  '80012345-6',
        razonSocial:                'Volvo do Brasil',
        nombreFantasia:             'Volvo',
        pais:                       'BR',
        tipo:                       'FABRICANTE',
        monedaPrincipal:            'USD',
        diasCredito:                30,
        descuentoPagoAnticipadoPct: 5,
        email:                      'contact@volvo.com.br',
        telefono:                   '+55 11 5555-0000',
    );
}

it('creates a valid supplier', function () {
    $s = makeSupplier();
    expect($s->getRazonSocial())->toBe('Volvo do Brasil')
        ->and($s->getRucRutNit())->toBe('80012345-6')
        ->and($s->getDiasCredito())->toBe(30)
        ->and($s->isActivo())->toBeTrue();
});

it('rejects invalid tipo', function () {
    Supplier::create(rucRutNit: null, razonSocial: 'X', tipo: 'INVALIDO');
})->throws(InvalidSupplierDataException::class);

it('rejects empty razon social', function () {
    Supplier::create(rucRutNit: null, razonSocial: '');
})->throws(InvalidSupplierDataException::class);

it('rejects descuento > 100', function () {
    Supplier::create(
        rucRutNit: null, razonSocial: 'X',
        descuentoPagoAnticipadoPct: 150,
    );
})->throws(InvalidSupplierDataException::class);

it('rejects negative descuento', function () {
    Supplier::create(
        rucRutNit: null, razonSocial: 'X',
        descuentoPagoAnticipadoPct: -1,
    );
})->throws(InvalidSupplierDataException::class);

it('rejects dias credito > 365', function () {
    Supplier::create(
        rucRutNit: null, razonSocial: 'X',
        diasCredito: 400,
    );
})->throws(InvalidSupplierDataException::class);

it('updates score within range', function () {
    $s = makeSupplier();
    $s->updateScore(85);
    expect($s->getScoreActual())->toBe(85.0);
});

it('rejects score out of range', function () {
    makeSupplier()->updateScore(150);
})->throws(InvalidSupplierDataException::class);

it('blocks ops when score critico', function () {
    $s = makeSupplier();
    $s->updateScore(20);
    expect(fn() => $s->ensureCanBuy())->toThrow(InvalidSupplierDataException::class);
});

it('blocks when deactivated', function () {
    $s = makeSupplier();
    $s->deactivate();
    expect(fn() => $s->ensureCanBuy())->toThrow(InvalidSupplierDataException::class);
});
