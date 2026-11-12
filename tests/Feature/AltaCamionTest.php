<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // inventarioUsuario() vive en InventarioUxTest.php (mismo directorio de tests)
    $this->actingAs(inventarioUsuario(['vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar']));
});

function altaCamion(array $extra = []): array
{
    return $extra + [
        'numero_chasis' => 'ys2r4x20002118999', 'marca' => 'Scania', 'modelo' => 'R450', 'anio' => 2020,
        'tipo_vehiculo' => 'CAMION_TRACTO', 'estado' => 'DISPONIBLE',
        'moneda_costo' => 'USD', 'costo_origen_moneda' => 60000,
    ];
}

test('el formulario muestra secciones, marcas existentes y la cotización como campo editable', function () {
    DB::table('vehiculos')->insert([
        'numero_chasis' => 'EXISTE-1', 'marca' => 'VOLVO', 'modelo' => 'FH', 'anio' => 2019, 'tipo_vehiculo' => 'CAMION_TRACTO',
        'moneda_costo' => 'USD', 'costo_origen_usd' => 1, 'costo_origen_moneda' => 1, 'estado' => 'DISPONIBLE',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->get(route('vehicles.create'))
        ->assertOk()
        ->assertSeeInOrder(['Identificación del camión', 'Situación y origen', 'Costo y precios de venta', 'Fotos', 'Ficha técnica y sitio web'])
        ->assertSee('<option value="VOLVO">', false)
        ->assertSee('name="moneda_costo" value="USD"', false)
        ->assertDontSee('name="costo_origen_usd"', false);
});

test('costo en USD: se carga una vez, tasa 1 y chasis en mayúsculas', function () {
    $this->post(route('vehicles.store'), altaCamion())->assertRedirect();

    $v = DB::table('vehiculos')->where('numero_chasis', 'YS2R4X20002118999')->first();
    expect($v)->not->toBeNull()
        ->and((float) $v->costo_origen_usd)->toBe(60000.0)
        ->and((float) $v->tasa_cambio_compra)->toBe(1.0);
});

test('costo en guaraníes: el USD lo calcula el servidor con la cotización', function () {
    $this->post(route('vehicles.store'), altaCamion([
        'moneda_costo' => 'PYG', 'costo_origen_moneda' => 369000000, 'tasa_cambio_compra' => 6150, 'costo_origen_usd' => 1,
    ]))->assertRedirect();

    $v = DB::table('vehiculos')->where('numero_chasis', 'YS2R4X20002118999')->first();
    expect((float) $v->costo_origen_usd)->toBe(60000.0)
        ->and((float) $v->costo_origen_moneda)->toBe(369000000.0);
});

test('costo en guaraníes sin cotización o con una absurda es rechazado', function () {
    $this->post(route('vehicles.store'), altaCamion(['moneda_costo' => 'PYG', 'costo_origen_moneda' => 369000000]))
        ->assertSessionHasErrors('tasa_cambio_compra');
    $this->post(route('vehicles.store'), altaCamion(['moneda_costo' => 'PYG', 'costo_origen_moneda' => 369000000, 'tasa_cambio_compra' => 6.15]))
        ->assertSessionHasErrors('tasa_cambio_compra');

    expect(DB::table('vehiculos')->count())->toBe(0);
});

test('los errores se muestran junto a su campo y en un resumen con enlaces', function () {
    $this->from(route('vehicles.create'))
        ->post(route('vehicles.store'), altaCamion(['marca' => '']))
        ->assertRedirect(route('vehicles.create'));

    $this->get(route('vehicles.create'))
        ->assertSee('id="resumenErrores"', false)
        ->assertSee('href="#marca"', false)
        ->assertSee('id="marca-error"', false)
        ->assertSee('aria-describedby=marca-error', false);
});
