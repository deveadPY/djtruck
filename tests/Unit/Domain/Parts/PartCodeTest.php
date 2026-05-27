<?php

use App\Domain\Parts\Exceptions\InvalidPartCodeException;
use App\Domain\Parts\ValueObjects\PartCode;

it('uppercases and trims the code', function () {
    expect(PartCode::parse(' abc-123 ')->value())->toBe('ABC-123');
});

it('accepts alphanumeric with dash/dot/slash/underscore', function () {
    expect(PartCode::parse('mb.123/45_67')->value())->toBe('MB.123/45_67');
});

it('rejects empty code', function () {
    PartCode::parse('   ');
})->throws(InvalidPartCodeException::class);

it('rejects too-short code', function () {
    PartCode::parse('A');
})->throws(InvalidPartCodeException::class);

it('rejects code with invalid characters', function () {
    PartCode::parse('AB#@!');
})->throws(InvalidPartCodeException::class);

it('considers two codes equal when normalized', function () {
    $a = PartCode::parse(' abc ');
    $b = PartCode::parse('ABC');
    expect($a->equals($b))->toBeTrue();
});
