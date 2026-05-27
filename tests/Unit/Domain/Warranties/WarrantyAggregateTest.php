<?php

use App\Domain\Warranties\Aggregates\Warranty;
use App\Domain\Warranties\Exceptions\WarrantyException;
use App\Domain\Warranties\ValueObjects\WarrantyStatus;

function makeWarranty(): Warranty
{
    return Warranty::create(
        ventaId:     1,
        inicio:      new \DateTimeImmutable('today'),
        vencimiento: new \DateTimeImmutable('+1 year'),
        tipo:        'FABRICA',
        vehiculoId:  10,
        kmInicio:    0,
        kmLimite:    100000,
        cobertura:   'Motor y transmisión',
    );
}

it('creates a valid warranty', function () {
    $w = makeWarranty();
    expect($w->getEstado())->toBe(WarrantyStatus::VIGENTE);
});

it('rejects vencimiento before inicio', function () {
    Warranty::create(
        ventaId: 1,
        inicio: new \DateTimeImmutable('today'),
        vencimiento: new \DateTimeImmutable('yesterday'),
        vehiculoId: 1,
    );
})->throws(WarrantyException::class);

it('rejects warranty without vehiculo or repuesto', function () {
    Warranty::create(
        ventaId: 1,
        inicio: new \DateTimeImmutable('today'),
        vencimiento: new \DateTimeImmutable('+1 year'),
    );
})->throws(\InvalidArgumentException::class);

it('rejects invalid tipo', function () {
    Warranty::create(
        ventaId: 1,
        inicio: new \DateTimeImmutable('today'),
        vencimiento: new \DateTimeImmutable('+1 year'),
        tipo: 'INVALIDO',
        vehiculoId: 1,
    );
})->throws(\InvalidArgumentException::class);

it('rejects km limite <= km inicio', function () {
    Warranty::create(
        ventaId: 1,
        inicio: new \DateTimeImmutable('today'),
        vencimiento: new \DateTimeImmutable('+1 year'),
        vehiculoId: 1,
        kmInicio: 1000,
        kmLimite: 1000,
    );
})->throws(\InvalidArgumentException::class);

it('cannot reclaim on cancelled warranty', function () {
    $w = makeWarranty();
    $w->anular();
    expect(fn() => $w->ensureVigente())->toThrow(WarrantyException::class);
});

it('detects expired warranty', function () {
    $w = Warranty::create(
        ventaId: 1,
        inicio: new \DateTimeImmutable('-2 years'),
        vencimiento: new \DateTimeImmutable('-1 day'),
        vehiculoId: 1,
    );
    expect(fn() => $w->ensureVigente())->toThrow(WarrantyException::class);
});
