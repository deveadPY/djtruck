<?php

use App\Domain\Parts\Aggregates\Part;
use App\Domain\Parts\Exceptions\InsufficientPartStockException;
use App\Domain\Parts\ValueObjects\PartCode;

function makePart(float $stock = 10, float $costoUsd = 100, float $minimo = 0): Part
{
    return Part::create(
        codigo:           PartCode::parse('TEST-001'),
        descripcion:      'Repuesto de prueba',
        unidadMedida:     'UND',
        stockInicial:     $stock,
        stockMinimo:      $minimo,
        costoPromedioUsd: $costoUsd,
        precioVentaUsd:   null,
    );
}

it('updates costo promedio ponderado on receive', function () {
    $p = makePart(10, 100);
    // Recibe 10 unidades a $200 → promedio = (10*100 + 10*200) / 20 = 150
    $p->recibirStock(10, 200);

    expect($p->getCostoPromedioUsd())->toBe(150.0)
        ->and($p->getStock()->actual)->toBe(20.0);
});

it('despacha stock when sufficient', function () {
    $p = makePart(10, 100);
    $p->despacharStock(3);
    expect($p->getStock()->actual)->toBe(7.0);
});

it('throws when despachando more than disponible', function () {
    makePart(5)->despacharStock(10);
})->throws(InsufficientPartStockException::class);

it('reservar reduces disponible without reducing actual', function () {
    $p = makePart(10);
    $p->reservar(3);
    expect($p->getStock()->actual)->toBe(10.0)
        ->and($p->getStock()->disponible())->toBe(7.0);
});

it('liberarReserva restores disponible', function () {
    $p = makePart(10);
    $p->reservar(4);
    $p->liberarReserva(4);
    expect($p->getStock()->disponible())->toBe(10.0);
});

it('ajustarStock cannot go below comprometido', function () {
    $p = makePart(10);
    $p->reservar(5);
    expect(fn() => $p->ajustarStock(3))->toThrow(\InvalidArgumentException::class);
});

it('rejects creation with precio < costo', function () {
    expect(fn() => Part::create(
        codigo:           PartCode::parse('TEST-001'),
        descripcion:      'X',
        costoPromedioUsd: 100,
        precioVentaUsd:   50,
    ))->toThrow(\InvalidArgumentException::class);
});

it('produces consistent toArray', function () {
    $p = makePart(10, 100, 2);
    $p->actualizarPrecioVenta(200);
    $arr = $p->toArray();

    expect($arr['codigo'])->toBe('TEST-001')
        ->and($arr['stock_actual'])->toBe(10.0)
        ->and($arr['stock_minimo'])->toBe(2.0)
        ->and($arr['costo_promedio_usd'])->toBe(100.0)
        ->and($arr['precio_venta_usd'])->toBe(200.0)
        ->and($arr['activo'])->toBeTrue();
});
