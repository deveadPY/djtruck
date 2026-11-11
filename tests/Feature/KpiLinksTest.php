<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function usuarioConPermisos(array $permisos): User
{
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    $rol = Role::firstOrCreate(['name' => 'operador-' . md5(implode(',', $permisos)), 'guard_name' => 'web']);
    $rol->syncPermissions($permisos);
    $user = User::factory()->create(['activo' => true]);
    $user->assignRole($rol);

    return $user;
}

test('las tarjetas del dashboard llevan a su módulo', function () {
    $this->actingAs(usuarioConPermisos(['ventas.ver', 'vehiculos.ver', 'cuotas.ver']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="' . route('vehicles.index', ['estado' => 'STOCK']) . '"', false)
        ->assertSee('href="' . route('notificaciones.index', ['tab' => 'mora']) . '"', false);
});

test('sin permiso la tarjeta no se enlaza', function () {
    $this->actingAs(usuarioConPermisos(['ventas.ver']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('vehicles.index', ['estado' => 'STOCK']), false);
});

test('el listado de vehículos filtra por estado y por stock', function () {
    $this->actingAs(usuarioConPermisos(['vehiculos.ver']));
    foreach ([['A-1', 'DISPONIBLE'], ['B-2', 'EN_PREPARACION'], ['C-3', 'VENDIDO']] as [$chasis, $estado]) {
        DB::table('vehiculos')->insert([
            'numero_chasis' => $chasis, 'marca' => 'Marca' . $chasis, 'modelo' => 'M', 'anio' => 2020,
            'estado' => $estado, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $this->get(route('vehicles.index', ['estado' => 'STOCK']))
        ->assertOk()->assertSee('MarcaA-1')->assertSee('MarcaB-2')->assertDontSee('MarcaC-3');

    $this->get(route('vehicles.index', ['estado' => 'EN_PREPARACION']))
        ->assertOk()->assertSee('MarcaB-2')->assertDontSee('MarcaA-1');
});
