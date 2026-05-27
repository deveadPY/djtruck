<?php

use App\Domain\Customers\Exceptions\InvalidRucException;
use App\Domain\Customers\ValueObjects\Ruc;

it('normalizes RUC removing dots and spaces', function () {
    expect(Ruc::parse(' 80.012.345-6 ')->value())->toBe('80012345-6');
});

it('uppercases alphanumeric IDs (CI/passport)', function () {
    expect(Ruc::parse('abc12345')->value())->toBe('ABC12345');
});

it('rejects empty', function () {
    Ruc::parse('   ');
})->throws(InvalidRucException::class);

it('rejects too short', function () {
    Ruc::parse('AB1');
})->throws(InvalidRucException::class);

it('rejects symbols', function () {
    Ruc::parse('123!@#');
})->throws(InvalidRucException::class);
