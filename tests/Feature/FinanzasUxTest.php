<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permisos = ['finanzas.ver', 'facturas.ver', 'facturas.crear', 'cuotas.ver'];
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    Role::firstOrCreate(['name' => 'administracion', 'guard_name' => 'web'])->syncPermissions($permisos);

    $this->user = User::factory()->create(['activo' => true]);
    $this->user->assignRole('administracion');
    $this->actingAs($this->user);
});

function movimiento(string $caja, string $tipo, float $usd, ?string $refType): void
{
    DB::table('movimientos_caja')->insert([
        'caja_id' => DB::table('cajas')->where('codigo', $caja)->value('id'),
        'tipo' => $tipo, 'concepto' => 'Movimiento de prueba', 'moneda' => 'USD',
        'monto' => $usd, 'monto_usd' => $usd, 'ref_type' => $refType,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('ingresos y egresos del mes excluyen transferencias entre cajas', function () {
    movimiento('CAJA_CAPITAL', 'INGRESO', 100, 'venta');
    movimiento('CAJA_CHICA', 'EGRESO', 30, null);
    movimiento('CAJA_CAPITAL', 'EGRESO', 500, 'transferencia');
    movimiento('CAJA_CHICA', 'INGRESO', 500, 'transferencia');

    $this->get(route('finance.index'))
        ->assertOk()
        ->assertViewHas('ingresosMes', fn ($v) => (float) $v === 100.0)
        ->assertViewHas('egresosMes', fn ($v) => (float) $v === 30.0)
        ->assertDontSee("> 0 ? 'warning'", false);
});

test('la caja filtra totales por tipo y muestra etiquetas legibles', function () {
    movimiento('CAJA_CAPITAL', 'INGRESO', 100, 'recibo_cuota');
    movimiento('CAJA_CAPITAL', 'EGRESO', 40, 'factura_vehiculo');

    $this->get(route('finance.caja.show', ['CAJA_CAPITAL', 'tipo' => 'INGRESO']))
        ->assertOk()
        ->assertViewHas('totalesPeriodo', fn ($t) => (float) $t->ingresos_usd === 100.0 && (float) $t->egresos_usd === 0.0)
        ->assertSee('Cobro de cuota')
        ->assertSee('Neto en efectivo PYG')
        ->assertDontSee('recibo_cuota');
});

test('facturas paginadas con total filtrado de todo el filtro', function () {
    $provId = DB::table('proveedores')->insertGetId(['razon_social' => 'Prov Test', 'created_at' => now(), 'updated_at' => now()]);
    $rows = [];
    for ($i = 1; $i <= 30; $i++) {
        $rows[] = [
            'proveedor_id' => $provId, 'numero_factura' => "F-{$i}", 'fecha_factura' => now()->subDays($i)->toDateString(),
            'destino' => 'GASTO_OPERATIVO', 'moneda' => 'USD', 'subtotal' => 10, 'total_usd' => 10,
            'estado' => $i <= 5 ? 'PENDIENTE' : 'PAGADA', 'created_at' => now(), 'updated_at' => now(),
        ];
    }
    DB::table('facturas_proveedores')->insert($rows);

    $this->get(route('facturas.index'))
        ->assertOk()
        ->assertViewHas('facturas', fn ($p) => $p->count() === 25 && $p->total() === 30)
        ->assertViewHas('totalFiltrado', 300.0)
        ->assertSee('Total filtrado')
        ->assertSee('data-danger-confirm', false);

    $this->get(route('facturas.index', ['estado' => 'PENDIENTE']))
        ->assertOk()
        ->assertViewHas('facturas', fn ($p) => $p->total() === 5)
        ->assertViewHas('totalFiltrado', 50.0);
});

test('notificaciones: la paginación conserva la pestaña y las deudas descartadas de la campana siguen visibles', function () {
    $provId = DB::table('proveedores')->insertGetId(['razon_social' => 'Prov Notif', 'created_at' => now(), 'updated_at' => now()]);
    $base = ['proveedor_id' => $provId, 'fecha_factura' => now()->toDateString(), 'destino' => 'GASTO_OPERATIVO',
        'moneda' => 'PYG', 'subtotal' => 1, 'total_usd' => 150, 'estado' => 'PENDIENTE', 'created_at' => now(), 'updated_at' => now()];
    $visible    = DB::table('facturas_proveedores')->insertGetId($base + ['numero_factura' => 'VISIBLE-1']);
    $descartada = DB::table('facturas_proveedores')->insertGetId($base + ['numero_factura' => 'OCULTA-1']);
    DB::table('notificaciones_descartadas')->insert(['user_id' => $this->user->id, 'tipo' => 'pagar', 'referencia_id' => $descartada]);

    $this->get(route('notificaciones.index', ['tab' => 'facturas_pagar']))
        ->assertOk()
        ->assertViewHas('facturasPagar', fn ($p) => $p->total() === 2
            && collect($p->items())->pluck('factura_id')->sort()->values()->all() === [$visible, $descartada]
            && str_contains($p->url(2), 'tab=facturas_pagar'))
        ->assertSee('role="tablist"', false)
        ->assertSee('USD 150,00');
});

test('notificaciones: las alertas de stock descartadas no se muestran', function () {
    $base = ['activo' => true, 'stock_minimo' => 5, 'stock_actual' => 1, 'created_at' => now(), 'updated_at' => now()];
    $visible = DB::table('stock_repuestos')->insertGetId($base + ['codigo' => 'VIS-1', 'descripcion' => 'Visible']);
    $oculto  = DB::table('stock_repuestos')->insertGetId($base + ['codigo' => 'OCU-1', 'descripcion' => 'Oculto']);
    DB::table('notificaciones_descartadas')->insert(['user_id' => $this->user->id, 'tipo' => 'stock', 'referencia_id' => $oculto]);

    $this->get(route('notificaciones.index', ['tab' => 'stock']))
        ->assertOk()
        ->assertViewHas('repuestosBajos', fn ($p) => collect($p->items())->pluck('id')->all() === [$visible]);
});
