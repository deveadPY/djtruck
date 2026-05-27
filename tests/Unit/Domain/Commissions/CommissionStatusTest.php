<?php

use App\Domain\Commissions\ValueObjects\CommissionStatus;

it('transitions CALCULADA → APROBADA', function () {
    expect(CommissionStatus::CALCULADA->puedeTransicionarA(CommissionStatus::APROBADA))->toBeTrue();
});

it('transitions APROBADA → PAGADA', function () {
    expect(CommissionStatus::APROBADA->puedeTransicionarA(CommissionStatus::PAGADA))->toBeTrue();
});

it('blocks CALCULADA → PAGADA (must approve first)', function () {
    expect(CommissionStatus::CALCULADA->puedeTransicionarA(CommissionStatus::PAGADA))->toBeFalse();
});

it('PAGADA es terminal', function () {
    expect(CommissionStatus::PAGADA->puedeTransicionarA(CommissionStatus::CALCULADA))->toBeFalse()
        ->and(CommissionStatus::PAGADA->puedeTransicionarA(CommissionStatus::ANULADA))->toBeFalse();
});

it('ANULADA es terminal', function () {
    expect(CommissionStatus::ANULADA->puedeTransicionarA(CommissionStatus::CALCULADA))->toBeFalse();
});

it('allows anular from CALCULADA or APROBADA', function () {
    expect(CommissionStatus::CALCULADA->puedeTransicionarA(CommissionStatus::ANULADA))->toBeTrue()
        ->and(CommissionStatus::APROBADA->puedeTransicionarA(CommissionStatus::ANULADA))->toBeTrue();
});
