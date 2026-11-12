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

    $this->clienteId = lote2Cliente('Fletes Deudor', '0981 123-456');
});

function lote2Cliente(string $nombre, ?string $telefono = null): int
{
    return DB::table('clientes')->insertGetId([
        'razon_social' => $nombre, 'telefono' => $telefono, 'linea_credito_usd' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

/** Venta; si $cuotas no está vacío, crea plan con esas cuotas (capital 100 + interés 10 + mora 5 por defecto). */
function lote2Venta(int $clienteId, string $modalidad, array $cuotas = []): array
{
    $ventaId = DB::table('ventas')->insertGetId([
        'numero_venta' => 'V-' . uniqid(), 'cliente_id' => $clienteId, 'vendedor_id' => 1,
        'estado' => 'COMPLETADO', 'modalidad_pago' => $modalidad, 'precio_venta_moneda' => 1000,
        'precio_venta_usd' => 1000, 'valor_libro_snapshot' => 0, 'fecha_venta' => now()->toDateString(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    if (!$cuotas) {
        return [$ventaId, null, []];
    }
    $planId = DB::table('planes_cuotas')->insertGetId([
        'venta_id' => $ventaId, 'cliente_id' => $clienteId, 'tipo_plan' => 'MANUAL', 'capital_total' => 100,
        'capital_total_usd' => 100, 'numero_cuotas' => count($cuotas), 'fecha_primera_cuota' => now()->toDateString(),
        'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $ids = [];
    foreach ($cuotas as $i => $c) {
        $ids[] = DB::table('cuotas')->insertGetId($c + [
            'plan_cuotas_id' => $planId, 'venta_id' => $ventaId, 'numero_cuota' => $i + 1, 'total_cuotas' => count($cuotas),
            'capital' => 100, 'interes' => 10, 'interes_mora' => 5, 'monto_pagado' => 0, 'estado' => 'PENDIENTE',
            'fecha_vencimiento' => now()->addDays(10)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    return [$ventaId, $planId, $ids];
}

// ── COM-11: listado de ventas con saldo, mora y filtros ─────────────────────

test('el listado de ventas muestra saldo pendiente y marca de mora', function () {
    lote2Venta($this->clienteId, 'CUOTAS', [
        ['estado' => 'VENCIDA', 'fecha_vencimiento' => now()->subDays(5)->toDateString(), 'monto_pagado' => 15], // 100
        ['estado' => 'PAGADA', 'monto_pagado' => 115],                                                            // no suma
    ]);

    $html = $this->get(route('ventas.index'))->assertOk()->assertSee('Saldo')->getContent();
    expect($html)->toContain('$ 100,00')->toContain('Mora');
});

test('el filtro con saldo pendiente y por modalidad', function () {
    [$cuotasId]  = lote2Venta($this->clienteId, 'CUOTAS', [[]]);
    [$contadoId] = lote2Venta($this->clienteId, 'CONTADO');
    [$saldadaId] = lote2Venta($this->clienteId, 'CUOTAS', [['estado' => 'PAGADA', 'monto_pagado' => 115]]);
    $num = fn($id) => DB::table('ventas')->where('id', $id)->value('numero_venta');

    $this->get(route('ventas.index', ['con_saldo' => 1]))
        ->assertOk()
        ->assertSee($num($cuotasId))
        ->assertDontSee($num($contadoId))
        ->assertDontSee($num($saldadaId));

    $this->get(route('ventas.index', ['modalidad' => 'CONTADO']))
        ->assertOk()
        ->assertSee($num($contadoId))
        ->assertDontSee($num($cuotasId));

    // Modalidad inválida se ignora; los filtros se conservan en la paginación
    $this->get(route('ventas.index', ['modalidad' => "x' OR 1=1", 'con_saldo' => 1]))
        ->assertOk()
        ->assertSee($num($cuotasId))
        ->assertViewHas('modalidad', '')
        ->assertViewHas('ventas', fn($p) => str_contains($p->url(2), 'con_saldo=1'));
});

// ── Clientes: deuda y orden ─────────────────────────────────────────────────

test('el listado de clientes muestra la deuda y ordena por deuda', function () {
    $otro = lote2Cliente('Cliente Al Dia');
    lote2Venta($this->clienteId, 'CUOTAS', [
        ['estado' => 'VENCIDA', 'fecha_vencimiento' => now()->subDays(3)->toDateString()], // 115, vencida
        [],                                                                                  // 115
    ]);

    $html = $this->get(route('clientes.index'))->assertOk()->assertSee('Deuda')->getContent();
    expect($html)->toContain('$ 230,00')->toContain('Mora');

    $this->get(route('clientes.index', ['orden' => 'deuda']))
        ->assertOk()
        ->assertSeeInOrder(['Fletes Deudor', 'Cliente Al Dia']);

    $this->get(route('clientes.index', ['orden' => 'DROP']))->assertOk()->assertViewHas('orden', '');
});

// ── COM-7: recibo por WhatsApp ──────────────────────────────────────────────

test('la cuota pagada ofrece recibo por WhatsApp con teléfono normalizado', function () {
    [, $planId, [$pagadaId]] = lote2Venta($this->clienteId, 'CUOTAS', [
        ['estado' => 'PAGADA', 'monto_pagado' => 115, 'fecha_pago_efectivo' => now()->toDateString()],
        [],
    ]);

    $html = $this->withSession(['show_print_cuota' => $pagadaId])
        ->get(route('planes_cuotas.show', $planId))
        ->assertOk()
        ->assertSee('Enviar por WhatsApp')
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->getContent();

    expect($html)->toContain('https://wa.me/595981123456?text=')
        ->and(substr_count($html, 'https://wa.me/'))->toBe(2) // fila de la cuota + aviso tras el cobro
        ->and($html)->toContain(rawurlencode('cuota 1/2'))
        ->and($html)->toContain(rawurlencode('$ 115,00'));
});

test('sin teléfono no se muestra el botón de WhatsApp', function () {
    $sinTel = lote2Cliente('Sin Telefono');
    [, $planId] = lote2Venta($sinTel, 'CUOTAS', [['estado' => 'PAGADA', 'monto_pagado' => 115]]);

    $this->get(route('planes_cuotas.show', $planId))
        ->assertOk()
        ->assertDontSee('wa.me', false);
});
