<?php

use App\Domain\Leads\Aggregates\Lead;
use App\Domain\Leads\Exceptions\InvalidLeadTransitionException;
use App\Domain\Leads\ValueObjects\LeadStatus;

function makeLead(): Lead
{
    return Lead::capture(
        vehiculoId: 1,
        nombre:     'Juan Pérez',
        telefono:   '+595 981 222-333',
        email:      'juan@example.com',
        canal:      'WhatsApp',
        mensaje:    'Me interesa el camión Volvo'
    );
}

it('captures a valid lead', function () {
    $l = makeLead();
    expect($l->getEstado())->toBe(LeadStatus::NUEVO)
        ->and($l->getVehiculoId())->toBe(1);
});

it('rejects empty nombre', function () {
    Lead::capture(vehiculoId: 1, nombre: '', telefono: '123');
})->throws(\InvalidArgumentException::class);

it('rejects empty telefono', function () {
    Lead::capture(vehiculoId: 1, nombre: 'X', telefono: '');
})->throws(\InvalidArgumentException::class);

it('rejects invalid email', function () {
    Lead::capture(vehiculoId: 1, nombre: 'X', telefono: '123', email: 'not-email');
})->throws(\InvalidArgumentException::class);

it('rejects invalid canal', function () {
    Lead::capture(vehiculoId: 1, nombre: 'X', telefono: '123', canal: 'InstagramDM');
})->throws(\InvalidArgumentException::class);

it('asigna a vendedor', function () {
    $l = makeLead();
    $l->asignarA(42);
    expect($l->getAsignadoA())->toBe(42);
});

it('marcar contactado from nuevo', function () {
    $l = makeLead();
    $l->marcarContactado();
    expect($l->getEstado())->toBe(LeadStatus::CONTACTADO);
});

it('cerrar con venta desde contactado', function () {
    $l = makeLead();
    $l->marcarContactado();
    $l->cerrarConVenta(123);
    expect($l->getEstado())->toBe(LeadStatus::CERRADO);
});

it('cannot transition from terminal', function () {
    $l = makeLead();
    $l->marcarPerdido('Sin presupuesto');
    expect(fn() => $l->marcarContactado())
        ->toThrow(InvalidLeadTransitionException::class);
});

it('marcar perdido requires motivo', function () {
    $l = makeLead();
    expect(fn() => $l->marcarPerdido(''))->toThrow(\InvalidArgumentException::class);
});
