<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permisos = ['finanzas.ver', 'facturas.ver', 'cuotas.ver'];
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    Role::firstOrCreate(['name' => 'administracion', 'guard_name' => 'web'])->syncPermissions($permisos);

    $this->user = User::factory()->create(['activo' => true, 'name' => 'Cajera Uno']);
    $this->user->assignRole('administracion');
    $this->actingAs($this->user);
});

function movCaja(string $tipo, string $moneda, float $monto, float $usd, ?string $ref, string $fecha, string $concepto = 'Mov'): void
{
    DB::table('movimientos_caja')->insert([
        'caja_id' => DB::table('cajas')->where('codigo', 'CAJA_CAPITAL')->value('id'),
        'tipo' => $tipo, 'concepto' => $concepto, 'moneda' => $moneda,
        'monto' => $monto, 'monto_usd' => $usd, 'ref_type' => $ref,
        'created_at' => $fecha, 'updated_at' => $fecha, 'created_by' => test()->user->id,
    ]);
}

test('la caja filtra por origen y moneda y calcula saldo inicial y final', function () {
    $antes = now()->startOfMonth()->subDays(5)->toDateTimeString();
    $hoy   = now()->toDateTimeString();
    movCaja('INGRESO', 'USD', 1000, 1000, 'venta', $antes);
    movCaja('EGRESO', 'USD', 200, 200, 'compra', $antes);
    movCaja('INGRESO', 'USD', 300, 300, 'venta', $hoy);
    movCaja('EGRESO', 'PYG', 750000, 100, 'factura_vehiculo', $hoy);

    $this->get(route('finance.caja.show', 'CAJA_CAPITAL'))
        ->assertOk()
        ->assertViewHas('saldosPeriodo', fn ($s) => $s === ['moneda' => 'USD', 'inicial' => 800.0, 'final' => 1000.0])
        ->assertSee('Saldo inicial')
        ->assertSee('export=csv', false);

    $this->get(route('finance.caja.show', ['CAJA_CAPITAL', 'origen' => 'venta']))
        ->assertOk()
        ->assertViewHas('movimientos', fn ($p) => $p->total() === 1)
        ->assertViewHas('totalesPeriodo', fn ($t) => (float) $t->ingresos_usd === 300.0 && (float) $t->egresos_usd === 0.0)
        ->assertViewHas('saldosPeriodo', fn ($s) => $s['inicial'] === 1000.0 && $s['final'] === 1300.0);

    // Con moneda filtrada el saldo va en esa moneda (monto, no monto_usd).
    $this->get(route('finance.caja.show', ['CAJA_CAPITAL', 'moneda' => 'PYG']))
        ->assertOk()
        ->assertViewHas('movimientos', fn ($p) => $p->total() === 1)
        ->assertViewHas('saldosPeriodo', fn ($s) => $s === ['moneda' => 'PYG', 'inicial' => 0.0, 'final' => -750000.0]);

    // Valores desconocidos se ignoran.
    $this->get(route('finance.caja.show', ['CAJA_CAPITAL', 'origen' => "x' OR 1=1", 'moneda' => 'EUR']))
        ->assertOk()
        ->assertViewHas('movimientos', fn ($p) => $p->total() === 2);
});

test('la caja exporta CSV filtrado con BOM, separador ; y fórmulas neutralizadas', function () {
    movCaja('INGRESO', 'USD', 50, 50, 'manual', now()->toDateTimeString(), '=HYPERLINK("http://x")');
    movCaja('EGRESO', 'USD', 20, 20, 'compra', now()->toDateTimeString(), 'No debe salir');

    $res = $this->get(route('finance.caja.show', ['CAJA_CAPITAL', 'origen' => 'manual', 'export' => 'csv']));
    $res->assertOk();
    expect($res->headers->get('content-disposition'))->toContain('.csv');

    $csv = $res->streamedContent();
    expect($csv)->toStartWith("\xEF\xBB\xBFFecha;Tipo;Origen;Concepto;Moneda;Monto;\"Monto USD\";Usuario")
        ->toContain(';INGRESO;Manual;"\'=HYPERLINK(""http://x"")";USD;50,00;50,00;"Cajera Uno"')
        ->not->toContain('No debe salir');
});

test('el PDF de caja aplica los filtros de origen y moneda', function () {
    movCaja('INGRESO', 'USD', 50, 50, 'manual', now()->toDateTimeString());

    $this->get(route('finance.caja.reporte', ['CAJA_CAPITAL', 'origen' => 'manual', 'moneda' => 'USD']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('mora muestra WhatsApp con teléfono paraguayo normalizado y Registrar pago', function () {
    $clienteId = DB::table('clientes')->insertGetId([
        'razon_social' => 'Fletes Mora', 'telefono' => '0981 123-456', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $sinTel = DB::table('clientes')->insertGetId([
        'razon_social' => 'Sin Telefono', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $planes = [];
    foreach ([$clienteId, $sinTel] as $i => $cid) {
        $ventaId = DB::table('ventas')->insertGetId([
            'numero_venta' => "V-MORA-{$i}", 'cliente_id' => $cid, 'vendedor_id' => 1, 'estado' => 'COMPLETADO',
            'precio_venta_moneda' => 110, 'precio_venta_usd' => 110, 'valor_libro_snapshot' => 0,
            'fecha_venta' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $planes[$i] = DB::table('planes_cuotas')->insertGetId([
            'venta_id' => $ventaId, 'cliente_id' => $cid, 'tipo_plan' => 'MANUAL', 'capital_total' => 100,
            'capital_total_usd' => 100, 'numero_cuotas' => 3, 'fecha_primera_cuota' => now()->subDays(10)->toDateString(),
            'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('cuotas')->insert([
            'plan_cuotas_id' => $planes[$i], 'venta_id' => $ventaId, 'numero_cuota' => 2, 'total_cuotas' => 3,
            'capital' => 100, 'interes' => 10, 'interes_mora' => 5, 'monto_pagado' => 0, 'moneda' => 'USD',
            'estado' => 'PENDIENTE', 'fecha_vencimiento' => now()->subDays(10)->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $res = $this->get(route('notificaciones.index', ['tab' => 'mora']))->assertOk();
    $texto = rawurlencode('Hola Fletes Mora, le recordamos que la cuota 2/3 de la venta V-MORA-0 está vencida hace 10 días. Monto vencido: USD 115,00. Gracias.');

    $res->assertSee('https://wa.me/595981123456?text=' . $texto, false)
        ->assertSee(route('planes_cuotas.show', $planes[0]), false)
        ->assertSee(route('planes_cuotas.show', $planes[1]), false)
        ->assertSee('Registrar pago');
    expect(substr_count($res->getContent(), 'https://wa.me/'))->toBe(1);
});

test('stock ofrece selección múltiple accesible usando el descarte en bloque existente', function () {
    $id = DB::table('stock_repuestos')->insertGetId([
        'codigo' => 'STK-1', 'descripcion' => 'Filtro', 'activo' => true, 'stock_minimo' => 5, 'stock_actual' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->get(route('notificaciones.index', ['tab' => 'stock']))
        ->assertOk()
        ->assertSee('Descartar seleccionadas')
        ->assertSee('for="stock-check-' . $id . '"', false)
        ->assertSee(route('api.notificaciones.descartar-todas'), false);

    $this->postJson(route('api.notificaciones.descartar-todas'), ['items' => [['tipo' => 'stock', 'referencia_id' => $id]]])
        ->assertOk();
    $this->get(route('notificaciones.index', ['tab' => 'stock']))
        ->assertViewHas('repuestosBajos', fn ($p) => $p->total() === 0);
});
