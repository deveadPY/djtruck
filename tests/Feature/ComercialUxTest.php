<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permisos = ['cuotas.ver', 'cuotas.pagar', 'ventas.ver', 'ventas.crear', 'clientes.ver'];
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    Role::firstOrCreate(['name' => 'cobrador', 'guard_name' => 'web'])->syncPermissions($permisos);

    $this->user = User::factory()->create(['activo' => true]);
    $this->user->assignRole('cobrador');
    $this->actingAs($this->user);

    $this->clienteId = DB::table('clientes')->insertGetId([
        'razon_social' => 'Fletes Test', 'telefono' => '0981 123-456', 'linea_credito_usd' => 50000,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->vehiculoId = DB::table('vehiculos')->insertGetId([
        'numero_chasis' => 'COM-001', 'marca' => 'Scania', 'modelo' => 'R450', 'anio' => 2021,
        'estado' => 'DISPONIBLE', 'costo_origen_usd' => 60000, 'created_at' => now(), 'updated_at' => now(),
    ]);
});

/** Venta con plan y una cuota de 100 capital + 10 interés + 5 mora. */
function planConCuota(int $clienteId, ?int $vehiculoId, array $cuota = []): array
{
    $ventaId = DB::table('ventas')->insertGetId([
        'numero_venta' => 'V-' . uniqid(), 'cliente_id' => $clienteId, 'vehiculo_id' => $vehiculoId,
        'vendedor_id' => 1, 'estado' => 'COMPLETADO', 'precio_venta_moneda' => 110, 'precio_venta_usd' => 110,
        'valor_libro_snapshot' => 0, 'fecha_venta' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $planId = DB::table('planes_cuotas')->insertGetId([
        'venta_id' => $ventaId, 'cliente_id' => $clienteId, 'tipo_plan' => 'MANUAL', 'capital_total' => 100,
        'capital_total_usd' => 100, 'numero_cuotas' => 1, 'fecha_primera_cuota' => now()->subDays(10)->toDateString(),
        'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cuotaId = DB::table('cuotas')->insertGetId($cuota + [
        'plan_cuotas_id' => $planId, 'venta_id' => $ventaId, 'numero_cuota' => 1, 'total_cuotas' => 1,
        'capital' => 100, 'interes' => 10, 'interes_mora' => 5, 'monto_pagado' => 0, 'estado' => 'VENCIDA',
        'fecha_vencimiento' => now()->subDays(10)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$ventaId, $planId, $cuotaId];
}

// ── COM-1 / COM-6 / COM-12: plan de cuotas ──────────────────────────────────

test('el cobro de cuota incluye la mora y el saldo del plan', function () {
    [, $planId] = planConCuota($this->clienteId, $this->vehiculoId);

    $this->get(route('planes_cuotas.show', $planId))
        ->assertOk()
        ->assertSee('Mora')
        ->assertSee('Saldo pendiente')
        ->assertSee('Próximo vencimiento')
        ->assertSee(', 100, 10, 5, 0, 115)', false)  // capital, interés, mora, pagado, saldo real
        ->assertSee('type="date" name="fecha_pago"', false)
        ->assertDontSee('⏳');
});

test('una cuota pagada en parte se puede terminar de cobrar', function () {
    [, $planId, $cuotaId] = planConCuota($this->clienteId, $this->vehiculoId);

    // Paga capital + interés sin la mora: la mora se imputa primero y queda saldo
    $this->post(route('cuotas.pagar', $cuotaId), ['fecha_pago' => now()->toDateString(), 'monto_pagado' => 110])
        ->assertSessionHasNoErrors();
    expect(DB::table('cuotas')->where('id', $cuotaId)->value('estado'))->toBe('PAGADA_PARCIAL');

    // La vista sigue ofreciendo cobrar el saldo (5)
    $this->get(route('planes_cuotas.show', $planId))
        ->assertOk()
        ->assertSee("openPayModal({$cuotaId},", false)
        ->assertSee(', 100, 10, 5, 110, 5)', false);

    // Pagar el saldo deja la cuota PAGADA con la fecha elegida
    $fecha = now()->subDay()->toDateString();
    $this->post(route('cuotas.pagar', $cuotaId), ['fecha_pago' => $fecha, 'monto_pagado' => 5])
        ->assertSessionHasNoErrors();
    $cuota = DB::table('cuotas')->where('id', $cuotaId)->first();
    expect($cuota->estado)->toBe('PAGADA')
        ->and((float) $cuota->monto_pagado)->toBe(115.0)
        ->and($cuota->fecha_pago_efectivo)->toBe($fecha);
});

// ── INV-4 / COM-3 / COM-4: nueva venta ──────────────────────────────────────

test('la nueva venta precarga vehículo y cliente válidos', function () {
    $this->get(route('ventas.create', ['vehiculo_id' => $this->vehiculoId, 'cliente_id' => $this->clienteId]))
        ->assertOk()
        ->assertSee('"itemable_id":' . $this->vehiculoId, false)
        ->assertSee('"cliente_id":' . $this->clienteId, false);
});

test('la nueva venta ignora vehículos no disponibles en la precarga', function () {
    DB::table('vehiculos')->where('id', $this->vehiculoId)->update(['estado' => 'VENDIDO']);

    $this->get(route('ventas.create', ['vehiculo_id' => $this->vehiculoId, 'cliente_id' => 'x']))
        ->assertOk()
        ->assertDontSee('"itemable_id":' . $this->vehiculoId, false)
        ->assertSee('restaurarVentaAnterior(null)', false);
});

test('las tarjetas de cliente muestran crédito disponible y cuotas vencidas', function () {
    planConCuota($this->clienteId, $this->vehiculoId);

    $this->get(route('ventas.create'))
        ->assertOk()
        ->assertSee('Crédito disponible US$ 49.885,00')
        ->assertSee('Cuotas vencidas');
});

// ── COM-5 / COM-8: ficha del cliente ────────────────────────────────────────

test('la ficha del cliente muestra cobranza, contacto y ventas sin vehículo', function () {
    [$ventaId, $planId] = planConCuota($this->clienteId, null);
    $numero = DB::table('ventas')->where('id', $ventaId)->value('numero_venta');

    $this->get(route('clientes.show', $this->clienteId))
        ->assertOk()
        ->assertSee(route('ventas.show', $ventaId), false)          // venta sin vehículo en el historial
        ->assertSee($numero)
        ->assertSee(route('planes_cuotas.show', $planId), false)
        ->assertSee('Cobrar')
        ->assertSee('115,00')
        ->assertSee('href="tel:0981123456"', false)
        ->assertSee('https://wa.me/595981123456', false)
        ->assertSee(route('ventas.create', ['cliente_id' => $this->clienteId]), false);
});

// ── COM-9: impresión no bloqueante ──────────────────────────────────────────

test('tras registrar la venta se ofrece imprimir sin confirm nativo', function () {
    [$ventaId] = planConCuota($this->clienteId, $this->vehiculoId);

    $this->withSession(['show_print_modal' => true])
        ->get(route('ventas.show', $ventaId))
        ->assertOk()
        ->assertDontSee('confirm(\'¿Desea imprimir', false)
        ->assertSee(route('ventas.imprimir', $ventaId), false);
});
