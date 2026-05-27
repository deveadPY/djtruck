<?php

use App\Domain\Auth\TwoFactor\ValueObjects\RecoveryCode;

it('generates code with format xxxx-xxxx-xxxx', function () {
    $code = RecoveryCode::generate();
    expect($code)->toMatch('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/');
});

it('excludes ambiguous characters (O 0 I 1)', function () {
    for ($i = 0; $i < 20; $i++) {
        $code = RecoveryCode::generate();
        expect($code)->not->toContain('O')
            ->and($code)->not->toContain('0')
            ->and($code)->not->toContain('I')
            ->and($code)->not->toContain('1');
    }
});

it('generates set of N codes', function () {
    $set = RecoveryCode::generateSet(8);
    expect($set)->toHaveCount(8)
        ->and(count(array_unique($set)))->toBe(8); // todos distintos (estadísticamente seguro)
});

it('normalizes uppercase and trims', function () {
    expect(RecoveryCode::normalize(' abc1-def2-ghi3 '))->toBe('ABC1-DEF2-GHI3');
});
