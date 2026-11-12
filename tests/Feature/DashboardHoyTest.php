<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function usuarioDashboardHoy(array $permisos): User
{
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    $rol = Role::firstOrCreate(['name' => 'hoy-' . md5(implode(',', $permisos)), 'guard_name' => 'web']);
    $rol->syncPermissions($permisos);
    $user = User::factory()->create(['activo' => true]);
    $user->assignRole($rol);

    return $user;
}

/** Crea venta + plan y devuelve el id del plan; las cuotas se pasan como [capital, interes, estado, fecha]. */
function planConCuotasHoy(string $cliente, array $cuotas): int
{
    $clienteId = DB::table('clientes')->insertGetId([
        'razon_social' => $cliente, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $ventaId = DB::table('ventas')->insertGetId([
        'numero_venta' => 'V-' . uniqid(), 'cliente_id' => $clienteId, 'vehiculo_id' => null,
        'vendedor_id' => 1, 'estado' => 'COMPLETADO', 'precio_venta_moneda' => 1000, 'precio_venta_usd' => 1000,
        'valor_libro_snapshot' => 0, 'fecha_venta' => '2026-01-01', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $planId = DB::table('planes_cuotas')->insertGetId([
        'venta_id' => $ventaId, 'cliente_id' => $clienteId, 'tipo_plan' => 'MANUAL', 'capital_total' => 1000,
        'capital_total_usd' => 1000, 'numero_cuotas' => count($cuotas), 'fecha_primera_cuota' => '2026-01-01',
        'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now(),
    ]);
    foreach ($cuotas as $i => [$capital, $interes, $estado, $fecha]) {
        DB::table('cuotas')->insert([
            'plan_cuotas_id' => $planId, 'venta_id' => $ventaId, 'numero_cuota' => $i + 1, 'total_cuotas' => count($cuotas),
            'capital' => $capital, 'interes' => $interes, 'interes_mora' => 0, 'monto_pagado' => 0, 'estado' => $estado,
            'fecha_vencimiento' => $fecha, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    return $planId;
}

afterEach(fn () => Carbon::setTestNow());

test('la franja Hoy usa la fecha de Asunción y suma cuotas de hoy y en mora', function () {
    // 02:00 UTC del 12/11 sigue siendo 11/11 en Asunción (UTC-3).
    Carbon::setTestNow('2026-11-12 02:00:00');
    $this->actingAs(usuarioDashboardHoy(['cuotas.ver']));

    $planHoy = planConCuotasHoy('Cliente Hoy', [
        [1000, 234, 'PENDIENTE', '2026-11-11'],
        [500, 0, 'PENDIENTE', '2026-11-12'], // mañana en Asunción: no cuenta como hoy
    ]);
    planConCuotasHoy('Cliente Moroso', [
        [300, 0, 'VENCIDA', '2026-10-01'],
        [200, 0, 'PENDIENTE', '2026-11-01'], // pendiente con fecha pasada también es mora
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Vencen hoy', '1', 'cuota · $1.234'])
        ->assertSeeInOrder(['En mora', '2', 'cuotas · $500 vencido'])
        ->assertSee('Cliente Hoy')
        ->assertSee('href="' . route('planes_cuotas.show', $planHoy) . '"', false)
        ->assertSee('href="' . route('notificaciones.index', ['tab' => 'hoy']) . '"', false)
        ->assertSee('href="' . route('notificaciones.index', ['tab' => 'mora']) . '"', false);
});

test('con más de 5 cuotas hoy no se lista el detalle', function () {
    Carbon::setTestNow('2026-11-12 15:00:00');
    $this->actingAs(usuarioDashboardHoy(['cuotas.ver']));
    $plan = planConCuotasHoy('Cliente Muchas', array_fill(0, 6, [100, 0, 'PENDIENTE', '2026-11-12']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Vencen hoy', '6', 'cuotas · $600'])
        ->assertDontSee(route('planes_cuotas.show', $plan), false);
});

test('vehículos en preparación enlazan al listado filtrado', function () {
    $this->actingAs(usuarioDashboardHoy(['vehiculos.ver']));
    DB::table('vehiculos')->insert([
        'numero_chasis' => 'PREP-1', 'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2020,
        'estado' => 'EN_PREPARACION', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['En preparación', '1', 'vehículo'])
        ->assertSee('href="' . route('vehicles.index', ['estado' => 'EN_PREPARACION']) . '"', false);
});

test('las acciones rápidas respetan los permisos', function () {
    $this->actingAs(usuarioDashboardHoy(['ventas.crear', 'clientes.crear']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Acciones rápidas')
        ->assertSee('href="' . route('ventas.create') . '"', false)
        ->assertSee('href="' . route('clientes.create') . '"', false)
        ->assertDontSee(route('vehicles.create'), false)
        ->assertDontSee('Registrar gasto');

    $this->actingAs(usuarioDashboardHoy(['vehiculos.ver', 'vehiculos.editar', 'vehiculos.crear']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Registrar gasto')
        ->assertSee('href="' . route('vehicles.create') . '"', false)
        ->assertDontSee(route('ventas.create'), false);
});

test('sin permisos no aparecen acciones ni enlaces de cobro', function () {
    $this->actingAs(usuarioDashboardHoy(['ventas.ver']));
    $plan = planConCuotasHoy('Cliente Oculto', [[100, 0, 'PENDIENTE', now('America/Asuncion')->toDateString()]]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Acciones rápidas')
        ->assertDontSee(route('planes_cuotas.show', $plan), false)
        ->assertDontSee(route('notificaciones.index', ['tab' => 'hoy']), false);
});
