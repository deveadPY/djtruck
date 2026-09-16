<?php

declare(strict_types=1);

use App\Application\Auth\TwoFactor\VerifyTwoFactorUseCase;
use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create(['password' => Hash::make('secreto123')]);
    $service = app(TwoFactorService::class);
    $service->startSetup($this->user);
    $this->user->forceFill(['two_factor_confirmed_at' => now()])->save();
    $this->codes = $service->getRecoveryCodes($this->user);
});

test('sin verificar el segundo factor no se pueden ver los recovery codes', function () {
    $this->actingAs($this->user)
        ->get('/2fa/recovery-codes')
        ->assertRedirect(route('2fa.verify'));
});

test('sin verificar el segundo factor no se pueden regenerar los recovery codes', function () {
    $this->actingAs($this->user)
        ->post('/2fa/regenerate-codes', ['password' => 'secreto123'])
        ->assertRedirect(route('2fa.verify'));

    expect(app(TwoFactorService::class)->getRecoveryCodes($this->user->fresh()))->toBe($this->codes);
});

test('sin verificar el segundo factor no se puede desactivar el 2FA', function () {
    $this->actingAs($this->user)
        ->post('/2fa/disable', ['password' => 'secreto123'])
        ->assertRedirect(route('2fa.verify'));

    expect(app(TwoFactorService::class)->isEnabled($this->user->fresh()))->toBeTrue();
});

test('con sesión verificada se muestran los recovery codes', function () {
    $this->actingAs($this->user)
        ->withSession([VerifyTwoFactorUseCase::SESSION_KEY => now()->toIso8601String()])
        ->get('/2fa/recovery-codes')
        ->assertOk()
        ->assertSee($this->codes[0]);
});

test('con sesión verificada y contraseña correcta el usuario desactiva su 2FA', function () {
    $this->actingAs($this->user)
        ->withSession([VerifyTwoFactorUseCase::SESSION_KEY => now()->toIso8601String()])
        ->post('/2fa/disable', ['password' => 'secreto123'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect(app(TwoFactorService::class)->isEnabled($this->user->fresh()))->toBeFalse();
});

test('con contraseña incorrecta el 2FA sigue activo', function () {
    $this->actingAs($this->user)
        ->withSession([VerifyTwoFactorUseCase::SESSION_KEY => now()->toIso8601String()])
        ->from('/dashboard')
        ->post('/2fa/disable', ['password' => 'incorrecta'])
        ->assertSessionHasErrors('password');

    expect(app(TwoFactorService::class)->isEnabled($this->user->fresh()))->toBeTrue();
});
