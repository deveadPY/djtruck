<?php

declare(strict_types=1);

use App\Application\Auth\TwoFactor\VerifyTwoFactorUseCase;
use App\Domain\Auth\TwoFactor\Services\TwoFactorService;
use App\Infrastructure\Persistence\Eloquent\Models\EmpresaConfigModel;
use App\Infrastructure\Settings\EmpresaSettings;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();

    foreach (['configuracion.ver', 'configuracion.editar'] as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
        ->syncPermissions(['configuracion.ver', 'configuracion.editar']);
    Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web'])
        ->syncPermissions(['configuracion.ver']);

    $this->admin = User::factory()->create(['password' => Hash::make('secreto123')]);
    $this->admin->assignRole('admin');
    app(TwoFactorService::class)->startSetup($this->admin);
    $this->admin->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->verified = [VerifyTwoFactorUseCase::SESSION_KEY => now()->toIso8601String()];
});

function desactivar2fa(): void
{
    EmpresaConfigModel::firstOrCreate(['id' => 1])->forceFill(['two_factor_enabled' => false])->save();
    EmpresaSettings::forget();
}

test('el 2FA viene activo por defecto', function () {
    expect(EmpresaSettings::twoFactorEnabled())->toBeTrue();
});

test('la configuración muestra la sección de seguridad con el estado actual', function () {
    $this->actingAs($this->admin)->withSession($this->verified)
        ->get('/configuracion')
        ->assertOk()
        ->assertSee('Autenticación de 2 pasos (2FA)')
        ->assertSee('Activa')
        ->assertDontSee('Desactivada')
        ->assertSee(route('config.security.update'));
});

test('un admin desactiva el 2FA con su contraseña y queda auditado', function () {
    $this->actingAs($this->admin)->withSession($this->verified)
        ->post('/configuracion/seguridad', ['two_factor_enabled' => '0', 'password' => 'secreto123'])
        ->assertRedirect(route('config.index'))
        ->assertSessionHas('success', 'Autenticación de 2 pasos desactivada.');

    expect(EmpresaSettings::twoFactorEnabled())->toBeFalse();
    expect(DB::table('audit_logs')->where('action', '2FA_POLICY_DISABLED')->where('user_id', $this->admin->id)->exists())->toBeTrue();
    // La configuración personal se conserva para cuando se reactive.
    expect(app(TwoFactorService::class)->isEnabled($this->admin->fresh()))->toBeTrue();
});

test('con contraseña incorrecta no cambia la política', function () {
    $this->actingAs($this->admin)->withSession($this->verified)
        ->from('/configuracion')
        ->post('/configuracion/seguridad', ['two_factor_enabled' => '0', 'password' => 'incorrecta'])
        ->assertRedirect('/configuracion')
        ->assertSessionHasErrors('password');

    expect(EmpresaSettings::twoFactorEnabled())->toBeTrue();
});

test('sin contraseña falla la validación', function () {
    $this->actingAs($this->admin)->withSession($this->verified)
        ->post('/configuracion/seguridad', ['two_factor_enabled' => '0'])
        ->assertSessionHasErrors('password');

    expect(EmpresaSettings::twoFactorEnabled())->toBeTrue();
});

test('un usuario sin permiso de edición no puede cambiar la política', function () {
    $vendedor = User::factory()->create(['password' => Hash::make('secreto123')]);
    $vendedor->assignRole('vendedor');

    $this->actingAs($vendedor)
        ->post('/configuracion/seguridad', ['two_factor_enabled' => '0', 'password' => 'secreto123'])
        ->assertForbidden();

    expect(EmpresaSettings::twoFactorEnabled())->toBeTrue();
});

test('con 2FA activo, un usuario con 2FA configurado debe verificar su código', function () {
    $this->actingAs($this->admin)
        ->get('/configuracion')
        ->assertRedirect(route('2fa.verify'));
});

test('con 2FA activo, un admin sin 2FA es obligado a configurarlo', function () {
    $nuevo = User::factory()->create();
    $nuevo->assignRole('admin');

    $this->actingAs($nuevo)
        ->get('/configuracion')
        ->assertRedirect(route('2fa.setup'));
});

test('con 2FA desactivado se accede solo con contraseña', function () {
    desactivar2fa();
    $nuevo = User::factory()->create();
    $nuevo->assignRole('admin');

    $this->actingAs($this->admin)->get('/configuracion')->assertOk()->assertSee('Desactivada');
    expect(app(TwoFactorService::class)->isEnabled($this->admin->fresh()))->toBeTrue();
    $this->actingAs($nuevo)->get('/configuracion')->assertOk();
});

test('con 2FA desactivado los recovery codes siguen exigiendo verificación', function () {
    desactivar2fa();

    $this->actingAs($this->admin)
        ->get('/2fa/recovery-codes')
        ->assertRedirect(route('2fa.verify'));
});

test('al reactivar el 2FA vuelve a pedirse el código', function () {
    desactivar2fa();

    $this->actingAs($this->admin)
        ->post('/configuracion/seguridad', ['two_factor_enabled' => '1', 'password' => 'secreto123'])
        ->assertRedirect(route('config.index'));

    expect(EmpresaSettings::twoFactorEnabled())->toBeTrue();
    expect(DB::table('audit_logs')->where('action', '2FA_POLICY_ENABLED')->exists())->toBeTrue();

    $this->get('/configuracion')->assertRedirect(route('2fa.verify'));
});
