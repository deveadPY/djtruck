<?php

use App\Domain\Parts\Exceptions\InvalidStockLevelException;
use App\Domain\Parts\ValueObjects\StockLevel;

it('disponible is actual minus comprometido', function () {
    $s = StockLevel::of(10, 3, 2);
    expect($s->disponible())->toBe(7.0);
});

it('flags low stock when disponible <= minimo', function () {
    $s = StockLevel::of(5, 0, 5);
    expect($s->bajoMinimo())->toBeTrue();

    $s2 = StockLevel::of(10, 0, 5);
    expect($s2->bajoMinimo())->toBeFalse();
});

it('rejects negative actual stock', function () {
    StockLevel::of(-1);
})->throws(InvalidStockLevelException::class);

it('rejects commitment greater than actual', function () {
    StockLevel::of(5, 10);
})->throws(InvalidStockLevelException::class);

it('rejects negative minimum', function () {
    StockLevel::of(10, 0, -1);
})->throws(InvalidStockLevelException::class);

it('alcanzaPara verifies disponibilidad', function () {
    $s = StockLevel::of(10, 2);
    expect($s->alcanzaPara(8))->toBeTrue();
    expect($s->alcanzaPara(9))->toBeFalse();
});
