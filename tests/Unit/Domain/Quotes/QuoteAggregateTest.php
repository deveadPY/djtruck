<?php

use App\Domain\Quotes\Aggregates\Quote;
use App\Domain\Quotes\Exceptions\QuoteException;
use App\Domain\Quotes\ValueObjects\QuoteStatus;

function makeQuote(?\DateTimeImmutable $vigencia = null): Quote
{
    return Quote::create(
        numero:        'P-2026-00001',
        clienteId:     1,
        fechaEmision:  new \DateTimeImmutable('today'),
        vigenciaHasta: $vigencia ?? new \DateTimeImmutable('+15 days'),
        items: [
            ['itemable_id' => 1, 'itemable_type' => 'App\\...VehicleModel',
             'descripcion' => 'Camión Volvo', 'cantidad' => 1, 'precio_unitario_usd' => 80000],
        ],
    );
}

it('creates a valid quote in BORRADOR', function () {
    $q = makeQuote();
    expect($q->getEstado())->toBe(QuoteStatus::BORRADOR)
        ->and($q->getTotalUsd())->toBe(80000.0);
});

it('rejects creation without items', function () {
    Quote::create(
        numero: 'P-2026-00002', clienteId: 1,
        fechaEmision:  new \DateTimeImmutable('today'),
        vigenciaHasta: new \DateTimeImmutable('+1 day'),
        items: [],
    );
})->throws(QuoteException::class);

it('rejects vigencia anterior a fecha emision', function () {
    Quote::create(
        numero: 'P-2026-00003', clienteId: 1,
        fechaEmision:  new \DateTimeImmutable('today'),
        vigenciaHasta: new \DateTimeImmutable('-1 day'),
        items: [['itemable_id' => 1, 'itemable_type' => 'X', 'descripcion' => '—', 'cantidad' => 1, 'precio_unitario_usd' => 100]],
    );
})->throws(\InvalidArgumentException::class);

it('rejects descuento > subtotal', function () {
    Quote::create(
        numero: 'P-2026-00004', clienteId: 1,
        fechaEmision:  new \DateTimeImmutable('today'),
        vigenciaHasta: new \DateTimeImmutable('+1 day'),
        items: [['itemable_id' => 1, 'itemable_type' => 'X', 'descripcion' => '—', 'cantidad' => 1, 'precio_unitario_usd' => 100]],
        descuentoUsd: 200,
    );
})->throws(\InvalidArgumentException::class);

it('transitions correctly: BORRADOR → ENVIADO → ACEPTADO', function () {
    $q = makeQuote();
    $q->marcarEnviado();
    expect($q->getEstado())->toBe(QuoteStatus::ENVIADO);

    $q->marcarAceptado();
    expect($q->getEstado())->toBe(QuoteStatus::ACEPTADO);
});

it('blocks invalid transition from CERRADO terminal', function () {
    $q = makeQuote();
    $q->marcarRechazado();
    expect(fn() => $q->marcarEnviado())->toThrow(QuoteException::class);
});

it('detects expired quote', function () {
    $q = makeQuote(new \DateTimeImmutable('-1 day'));
    expect($q->estaVencido())->toBeTrue()
        ->and($q->puedeConvertirseAVenta())->toBeFalse();
});

it('aceptado y vigente puede convertirse a venta', function () {
    $q = makeQuote();
    $q->marcarEnviado();
    $q->marcarAceptado();
    expect($q->puedeConvertirseAVenta())->toBeTrue();
});

it('descuenta del subtotal correctamente', function () {
    $q = Quote::create(
        numero: 'P-2026-00010', clienteId: 1,
        fechaEmision:  new \DateTimeImmutable('today'),
        vigenciaHasta: new \DateTimeImmutable('+1 day'),
        items: [['itemable_id' => 1, 'itemable_type' => 'X', 'descripcion' => '—', 'cantidad' => 2, 'precio_unitario_usd' => 100]],
        descuentoUsd: 50,
    );
    expect($q->getTotalUsd())->toBe(150.0);
});
