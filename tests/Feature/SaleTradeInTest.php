<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permisos = ['ventas.crear', 'ventas.ver'];
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web'])->syncPermissions($permisos);

    $this->user = User::factory()->create(['activo' => true]);
    $this->user->assignRole('vendedor');
    $this->actingAs($this->user);

    $this->clienteId = DB::table('clientes')->insertGetId([
        'razon_social' => 'Transportes Test', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->vehiculoId = DB::table('vehiculos')->insertGetId([
        'numero_chasis' => 'VENTA-001', 'marca' => 'Scania', 'modelo' => 'R450', 'anio' => 2021,
        'estado' => 'DISPONIBLE', 'costo_origen_usd' => 60000, 'created_at' => now(), 'updated_at' => now(),
    ]);
});

function ventaConToma(int $clienteId, int $vehiculoId, array $canje): array
{
    return [
        'cliente_id' => $clienteId, 'fecha_venta' => now()->toDateString(), 'moneda_venta' => 'USD',
        'precio_venta_moneda' => 80000, 'precio_venta_usd' => 80000, 'estado' => 'COMPLETADO',
        'modalidad_pago' => 'CONTADO', 'vehiculo_id' => $vehiculoId,
        'items' => [[
            'itemable_id' => $vehiculoId, 'itemable_type' => 'App\\Models\\Vehicle',
            'descripcion' => 'Scania R450', 'cantidad' => 1, 'precio_unitario_usd' => 80000,
        ]],
        'pagos' => [['tipo' => 'VEHICULO_CANJE', 'monto_usd' => 80000, 'canje' => $canje]],
    ];
}

test('el vehículo recibido como parte de pago entra al stock en preparación', function () {
    $stockAntes = DB::table('vehiculos')->whereNotIn('estado', ['VENDIDO', 'BAJA'])->whereNull('deleted_at')->count();

    $this->post(route('ventas.store'), ventaConToma($this->clienteId, $this->vehiculoId, [
        'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2018, 'numero_chasis' => 'TOMA-001', 'kilometraje' => 450000,
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $toma = DB::table('vehiculos')->where('numero_chasis', 'TOMA-001')->first();
    $venta = DB::table('ventas')->latest('id')->first();

    expect($toma)->not->toBeNull()
        ->and($toma->estado)->toBe('EN_PREPARACION')
        ->and((float) $toma->valor_toma_usd)->toBe(80000.0)
        ->and((float) $toma->costo_origen_usd)->toBe(80000.0)
        ->and((int) $toma->venta_canje_origen_id)->toBe((int) $venta->id)
        ->and(DB::table('detalles_pago')->where('venta_id', $venta->id)->value('vehiculo_canje_id'))->toEqual($toma->id);

    // Sale el vendido y entra el recibido: el stock queda igual
    $stockDespues = DB::table('vehiculos')->whereNotIn('estado', ['VENDIDO', 'BAJA'])->whereNull('deleted_at')->count();
    expect($stockDespues)->toBe($stockAntes);
});

test('anular la venta da de baja el vehículo recibido y devuelve el vendido', function () {
    $this->post(route('ventas.store'), ventaConToma($this->clienteId, $this->vehiculoId, [
        'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2018, 'numero_chasis' => 'TOMA-002',
    ]))->assertSessionHasNoErrors();
    $ventaId = DB::table('ventas')->latest('id')->value('id');

    $this->delete(route('ventas.destroy', $ventaId), ['motivo' => 'Error de carga'])->assertSessionHasNoErrors();

    expect(DB::table('vehiculos')->where('numero_chasis', 'TOMA-002')->value('deleted_at'))->not->toBeNull()
        ->and(DB::table('vehiculos')->where('id', $this->vehiculoId)->value('estado'))->toBe('DISPONIBLE');
});

test('exige los datos del vehículo recibido', function () {
    $this->post(route('ventas.store'), ventaConToma($this->clienteId, $this->vehiculoId, []))
        ->assertSessionHasErrors('pagos.0.canje.numero_chasis');
});

test('no permite usar un vehículo del stock como parte de pago al crear', function () {
    $datos = ventaConToma($this->clienteId, $this->vehiculoId, []);
    $datos['pagos'][0]['vehiculo_canje_id'] = $this->vehiculoId;

    $this->post(route('ventas.store'), $datos)->assertSessionHasErrors('pagos.0.vehiculo_canje_id');
});

test('rechaza un chasis que ya existe', function () {
    $this->post(route('ventas.store'), ventaConToma($this->clienteId, $this->vehiculoId, [
        'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2018, 'numero_chasis' => 'VENTA-001',
    ]))->assertSessionHasErrors('pagos.0.canje.numero_chasis');
});

test('la pantalla de venta muestra los campos del vehículo recibido', function () {
    $this->get(route('ventas.create'))
        ->assertOk()
        ->assertSee('pagos[0][canje][numero_chasis]', false)
        ->assertSee('pagos[${idx}][canje][marca]', false);
});
