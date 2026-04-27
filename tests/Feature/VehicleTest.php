<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Crear usuario con permiso de vehículos
    $this->user = User::factory()->create([
        'password' => Hash::make('password'),
        'activo'   => true,
    ]);

    // Crear permisos si no existen
    $permisos = ['vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar', 'vehiculos.eliminar'];
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $role->syncPermissions($permisos);
    $this->user->assignRole('admin');

    $this->actingAs($this->user);
});

// ── Listado ───────────────────────────────────────────────────────────────

test('índice de vehículos es accesible', function () {
    $response = $this->get('/vehicles');
    $response->assertStatus(200);
});

test('índice de vehículos sin permiso devuelve 403', function () {
    $userSinPermisos = User::factory()->create(['activo' => true]);
    $this->actingAs($userSinPermisos);

    $response = $this->get('/vehicles');
    $response->assertStatus(403);
});

// ── Crear vehículo ────────────────────────────────────────────────────────

test('formulario de creación es accesible', function () {
    $response = $this->get('/vehicles/create');
    $response->assertStatus(200);
});

test('crear vehículo con datos válidos funciona', function () {
    $response = $this->post('/vehicles', [
        'numero_chasis'           => 'TEST-CHASIS-001',
        'marca'                   => 'Volvo',
        'modelo'                  => 'FH16',
        'año'                     => 2023,
        'tipo_vehiculo'           => 'CAMION',
        'estado'                  => 'DISPONIBLE',
        'costo_origen_usd'        => 80000,
        'precio_venta_sugerido_usd' => 95000,
        'moneda_costo'            => 'USD',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('vehiculos', ['numero_chasis' => 'TEST-CHASIS-001']);
});

test('crear vehículo sin chasis falla validación', function () {
    $response = $this->post('/vehicles', [
        'marca' => 'Volvo',
        'modelo' => 'FH16',
        'año' => 2023,
        'costo_origen_usd' => 80000,
    ]);

    $response->assertSessionHasErrors(['numero_chasis']);
});

test('no se pueden crear dos vehículos con el mismo chasis', function () {
    $chasis = 'DUPLICADO-001';

    $this->post('/vehicles', [
        'numero_chasis'           => $chasis,
        'marca'                   => 'Volvo',
        'modelo'                  => 'FH16',
        'año'                     => 2023,
        'tipo_vehiculo'           => 'CAMION',
        'estado'                  => 'DISPONIBLE',
        'costo_origen_usd'        => 80000,
        'precio_venta_sugerido_usd' => 95000,
        'moneda_costo'            => 'USD',
    ]);

    $response = $this->post('/vehicles', [
        'numero_chasis'           => $chasis,
        'marca'                   => 'Mercedes',
        'modelo'                  => 'Actros',
        'año'                     => 2023,
        'tipo_vehiculo'           => 'CAMION',
        'estado'                  => 'DISPONIBLE',
        'costo_origen_usd'        => 75000,
        'precio_venta_sugerido_usd' => 90000,
        'moneda_costo'            => 'USD',
    ]);

    $response->assertSessionHasErrors(['numero_chasis']);
});
