<?php

use App\Domain\Customers\Aggregates\Customer;
use App\Domain\Customers\Exceptions\InvalidCustomerDataException;
use App\Domain\Customers\ValueObjects\Ruc;

function makeCustomer(): Customer
{
    return Customer::create(
        ruc:             Ruc::parse('80012345-6'),
        razonSocial:     'ACME SA',
        nombreFantasia:  'ACME',
        pais:            'PY',
        email:           'info@acme.com',
        telefono:        '+595 21 555-1234',
        direccion:       'Asunción',
        lineaCreditoUsd: 50000,
        activo:          true,
    );
}

it('creates a valid customer', function () {
    $c = makeCustomer();
    expect($c->getRazonSocial())->toBe('ACME SA')
        ->and($c->getRuc()->value())->toBe('80012345-6')
        ->and($c->getEmail())->toBe('info@acme.com')
        ->and($c->getLineaCreditoUsd())->toBe(50000.0)
        ->and($c->isActivo())->toBeTrue();
});

it('lowercases email', function () {
    $c = Customer::create(
        ruc:             Ruc::parse('123-4'),
        razonSocial:     'X',
        nombreFantasia:  null,
        pais:            'py',
        email:           'JUAN@EXAMPLE.com',
        telefono:        null,
        direccion:       null,
    );
    expect($c->getEmail())->toBe('juan@example.com');
});

it('rejects empty razón social', function () {
    Customer::create(
        ruc: Ruc::parse('123-4'), razonSocial: '',
        nombreFantasia: null, pais: 'PY',
        email: null, telefono: null, direccion: null,
    );
})->throws(InvalidCustomerDataException::class);

it('rejects negative credit limit', function () {
    Customer::create(
        ruc: Ruc::parse('123-4'), razonSocial: 'X',
        nombreFantasia: null, pais: 'PY',
        email: null, telefono: null, direccion: null,
        lineaCreditoUsd: -100,
    );
})->throws(InvalidCustomerDataException::class);

it('rejects invalid email', function () {
    Customer::create(
        ruc: Ruc::parse('123-4'), razonSocial: 'X',
        nombreFantasia: null, pais: 'PY',
        email: 'not-an-email', telefono: null, direccion: null,
    );
})->throws(InvalidCustomerDataException::class);

it('updates credit limit', function () {
    $c = makeCustomer();
    $c->updateCreditLimit(80000);
    expect($c->getLineaCreditoUsd())->toBe(80000.0);
});

it('throws when updating to negative credit', function () {
    makeCustomer()->updateCreditLimit(-100);
})->throws(InvalidCustomerDataException::class);

it('blocks sale when deactivated', function () {
    $c = makeCustomer();
    $c->deactivate();
    expect($c->isActivo())->toBeFalse()
        ->and(fn() => $c->ensureCanSell())->toThrow(InvalidCustomerDataException::class);
});
