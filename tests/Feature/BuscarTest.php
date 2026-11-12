<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function usuarioBusqueda(array $permisos): User
{
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    $rol = Role::firstOrCreate(['name' => 'busqueda-' . md5(implode(',', $permisos)), 'guard_name' => 'web']);
    $rol->syncPermissions($permisos);
    $user = User::factory()->create(['activo' => true]);
    $user->assignRole($rol);

    return $user;
}

beforeEach(function () {
    $this->vehiculoId = DB::table('vehiculos')->insertGetId([
        'numero_chasis' => 'YS2R4X20009269901', 'marca' => 'Scania', 'modelo' => 'R450', 'anio' => 2020,
        'estado' => 'DISPONIBLE', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('vehiculos')->insert([
        'numero_chasis' => 'VOLVO-FH-02', 'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2019,
        'estado' => 'DISPONIBLE', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('clientes')->insert([
        'razon_social' => 'Transportes Scania SA', 'ruc' => '80012345-6', 'pais' => 'PY',
        'created_at' => now(), 'updated_at' => now(),
    ]);
});

test('un único resultado lleva directo a la ficha', function () {
    $this->actingAs(usuarioBusqueda(['vehiculos.ver']));

    $this->get(route('buscar', ['q' => '9269901']))->assertRedirect(route('vehicles.show', $this->vehiculoId));
});

test('solo busca en los módulos que el usuario puede ver', function () {
    $this->actingAs(usuarioBusqueda(['vehiculos.ver', 'clientes.ver']));
    $this->get(route('buscar', ['q' => 'scania']))
        ->assertOk()->assertSee('Transportes Scania SA')->assertSee('YS2R4X20009269901');

    $this->actingAs(usuarioBusqueda(['ventas.ver']));
    $this->get(route('buscar', ['q' => 'scania']))
        ->assertOk()->assertDontSee('Transportes Scania SA')->assertDontSee('YS2R4X20009269901');
});

test('los comodines de LIKE se buscan como texto', function () {
    $this->actingAs(usuarioBusqueda(['vehiculos.ver', 'clientes.ver']));

    $this->get(route('buscar', ['q' => '%%']))->assertOk()->assertSee('Sin resultados');
});
