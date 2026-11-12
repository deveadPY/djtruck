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
        'razon_social' => 'Transportes Rápidos', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->vehiculoId = DB::table('vehiculos')->insertGetId([
        'numero_chasis' => 'RAPIDA-001', 'marca' => 'Volvo', 'modelo' => 'FH 540', 'anio' => 2022,
        'estado' => 'DISPONIBLE', 'costo_origen_usd' => 30000,
        'precio_contado_usd' => 36000, 'precio_cuotas_usd' => 38000,
        'created_at' => now(), 'updated_at' => now(),
    ]);
});

test('la venta muestra la moneda como tarjetas con USD marcado por defecto', function () {
    $html = $this->get(route('ventas.create'))->assertOk()->getContent();

    foreach (['us', 'py', 'br'] as $bandera) {
        expect($html)->toContain("https://flagcdn.com/w40/{$bandera}.png");
    }
    expect($html)
        ->toMatch('/<input type="radio" name="moneda_venta" value="USD"[^>]*checked/')
        ->not->toMatch('/<input type="radio" name="moneda_venta" value="PYG"[^>]*checked/')
        ->not->toContain('<select name="moneda_venta"');
});

test('la venta mantiene los campos del contrato y un solo campo de precio', function () {
    $html = $this->get(route('ventas.create'))->assertOk()->getContent();

    foreach (['precio_venta_moneda', 'descuento_moneda', 'tasa_cambio_venta', 'fecha_venta', 'modalidad_pago', 'estado'] as $campo) {
        expect($html)->toContain('name="' . $campo . '"');
    }
    // Los equivalentes en USD viajan ocultos
    expect($html)
        ->toMatch('/<input type="hidden" name="precio_venta_usd" id="precio_usd"/')
        ->toMatch('/<input type="hidden" name="descuento_usd" id="descuento_usd"/')
        ->and(substr_count($html, 'name="precio_venta_moneda"'))->toBe(1);
});

test('los precios del camión llegan redondeados a 2 decimales', function () {
    $this->get(route('ventas.create'))
        ->assertOk()
        ->assertSee('data-precio-cuotas="38000.00"', false)
        ->assertSee('data-precio-contado="36000.00"', false);
});

test('los pasos y modales son accesibles', function () {
    $html = $this->get(route('ventas.create'))->assertOk()->getContent();

    expect($html)
        ->toMatch('/<button type="button"[^>]*id="nav-step-1"[^>]*aria-current="step"/')
        ->toContain('id="modalConfirmacion" role="dialog" aria-modal="true" aria-labelledby="modalConfirmacionTitulo"')
        ->toContain('id="modalCliente" role="dialog" aria-modal="true" aria-labelledby="modalClienteTitulo"')
        ->toContain('<label for="pago_monto_0"');
});

test('una venta contado en USD de 38000 guarda exactamente 38000.00', function () {
    $this->post(route('ventas.store'), [
        'cliente_id' => $this->clienteId, 'fecha_venta' => now()->toDateString(), 'moneda_venta' => 'USD',
        'precio_venta_moneda' => '38000.00', 'precio_venta_usd' => '38000.00', 'tasa_cambio_venta' => '1',
        'descuento_moneda' => '0.00', 'descuento_usd' => '0.00',
        'estado' => 'COMPLETADO', 'modalidad_pago' => 'CONTADO', 'vehiculo_id' => $this->vehiculoId,
        'items' => [[
            'itemable_id' => $this->vehiculoId, 'itemable_type' => 'App\\Models\\Vehicle',
            'descripcion' => 'Volvo FH 540', 'cantidad' => 1, 'precio_unitario_usd' => '38000.00',
        ]],
        'pagos' => [['tipo' => 'EFECTIVO', 'monto_usd' => '38000.00']],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $venta = DB::table('ventas')->latest('id')->first();
    expect(number_format((float) $venta->precio_venta_usd, 2, '.', ''))->toBe('38000.00')
        ->and(number_format((float) $venta->precio_venta_moneda, 2, '.', ''))->toBe('38000.00')
        ->and(number_format((float) $venta->descuento_usd, 2, '.', ''))->toBe('0.00');
});
