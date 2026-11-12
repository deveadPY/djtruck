<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function inventarioUsuario(array $permisos): User
{
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    $rol = Role::firstOrCreate(['name' => 'inventario-' . md5(implode(',', $permisos)), 'guard_name' => 'web']);
    $rol->syncPermissions($permisos);
    $user = User::factory()->create(['activo' => true]);
    $user->assignRole($rol);

    return $user;
}

function inventarioVehiculo(string $chasis, string $estado, array $extra = []): int
{
    return DB::table('vehiculos')->insertGetId($extra + [
        'numero_chasis' => $chasis, 'marca' => 'Marca' . $chasis, 'modelo' => 'M', 'anio' => 2020,
        'tipo_vehiculo' => 'CAMION_TRACTO', 'moneda_costo' => 'USD', 'costo_origen_usd' => 50000,
        'costo_origen_moneda' => 50000, 'estado' => $estado, 'created_at' => now(), 'updated_at' => now(),
    ]);
}

function inventarioPayload(string $estado): array
{
    return [
        'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2020, 'tipo_vehiculo' => 'CAMION_TRACTO',
        'estado' => $estado, 'moneda_costo' => 'USD', 'costo_origen_usd' => 50000, 'costo_origen_moneda' => 50000,
    ];
}

// ── INV-1: el estado VENDIDO no se pierde al editar ───────────────────────

test('editar un vehículo vendido muestra el estado como solo lectura', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'vehiculos.editar']));
    $id = inventarioVehiculo('V-1', 'VENDIDO');

    $this->get(route('vehicles.edit', $id))
        ->assertOk()
        ->assertSee('<input type="hidden" name="estado" value="VENDIDO">', false)
        ->assertDontSee('<select name="estado"', false);
});

test('editar un vehículo disponible ofrece estados legibles sin VENDIDO', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'vehiculos.editar']));
    $id = inventarioVehiculo('D-1', 'DISPONIBLE');

    $this->get(route('vehicles.edit', $id))
        ->assertOk()
        ->assertSee('<select name="estado"', false)
        ->assertSee('En preparación')
        ->assertDontSee('value="VENDIDO"', false);
});

test('un vehículo vendido no cambia de estado desde el formulario', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'vehiculos.editar']));
    $id = inventarioVehiculo('V-2', 'VENDIDO');

    $this->put(route('vehicles.update', $id), inventarioPayload('DISPONIBLE'))->assertSessionHasErrors('estado');
    expect(DB::table('vehiculos')->where('id', $id)->value('estado'))->toBe('VENDIDO');

    $this->put(route('vehicles.update', $id), inventarioPayload('VENDIDO'))->assertSessionHasNoErrors();
    expect(DB::table('vehiculos')->where('id', $id)->value('estado'))->toBe('VENDIDO');
});

test('no se marca como vendido desde el formulario de edición', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'vehiculos.editar']));
    $id = inventarioVehiculo('D-2', 'DISPONIBLE');

    $this->put(route('vehicles.update', $id), inventarioPayload('VENDIDO'))->assertSessionHasErrors('estado');
    expect(DB::table('vehiculos')->where('id', $id)->value('estado'))->toBe('DISPONIBLE');
});

// ── INV-3: listado en stock por defecto ───────────────────────────────────

test('el listado muestra el stock por defecto y todos con TODOS', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver']));
    inventarioVehiculo('A-1', 'DISPONIBLE');
    inventarioVehiculo('B-2', 'RESERVADO');
    inventarioVehiculo('C-3', 'VENDIDO');
    inventarioVehiculo('D-4', 'BAJA');

    $this->get(route('vehicles.index'))
        ->assertOk()
        ->assertSee('MarcaA-1')->assertSee('MarcaB-2')
        ->assertDontSee('MarcaC-3')->assertDontSee('MarcaD-4')
        ->assertSee('2 resultados')
        ->assertDontSee('Unidades Disponibles')
        ->assertSee('Reservado');

    $this->get(route('vehicles.index', ['estado' => 'TODOS']))
        ->assertOk()
        ->assertSee('MarcaC-3')->assertSee('MarcaD-4')
        ->assertSee('4 resultados');
});

// ── INV-4: acciones del detalle ───────────────────────────────────────────

test('el detalle ofrece Vender en un vehículo disponible con permiso de venta', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'vehiculos.editar', 'ventas.crear']));
    $id = inventarioVehiculo('S-1', 'DISPONIBLE', ['kilometraje' => 123456]);

    $this->get(route('vehicles.show', $id))
        ->assertOk()
        ->assertSee(route('ventas.create', ['vehiculo_id' => $id]), false)
        ->assertSee('Factura de proveedor')
        ->assertSee(route('gastos.create', $id), false)
        ->assertSee('123.456 km')
        ->assertSee('data-danger-confirm', false)
        ->assertDontSee('return confirm(', false);
});

test('el detalle no ofrece Vender si está vendido o falta el permiso', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'ventas.crear']));
    $vendido = inventarioVehiculo('S-2', 'VENDIDO');
    $this->get(route('vehicles.show', $vendido))
        ->assertOk()
        ->assertDontSee(route('ventas.create', ['vehiculo_id' => $vendido]), false);

    $this->actingAs(inventarioUsuario(['vehiculos.ver']));
    $disponible = inventarioVehiculo('S-3', 'DISPONIBLE');
    $this->get(route('vehicles.show', $disponible))
        ->assertOk()
        ->assertDontSee(route('ventas.create', ['vehiculo_id' => $disponible]), false);
});

test('el detalle permite eliminar un gasto con confirmación', function () {
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'vehiculos.editar']));
    $id = inventarioVehiculo('G-1', 'DISPONIBLE');
    $gastoId = DB::table('gastos_vehiculo')->insertGetId([
        'vehiculo_id' => $id, 'concepto' => 'Frenos', 'categoria' => 'REPARACION_MECANICA', 'origen_tipo' => 'MANO_OBRA',
        'moneda' => 'USD', 'tasa_cambio' => 1, 'monto_moneda' => 100, 'monto_usd' => 100,
        'fecha_gasto' => '2026-11-11', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->get(route('vehicles.show', $id))
        ->assertOk()
        ->assertSee(route('gastos.destroy', [$id, $gastoId]), false);
});
