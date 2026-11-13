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
        'tipo_vehiculo' => 'CAMION_TRACTO', 'estado' => 'DISPONIBLE', 'costo_origen_usd' => 60000,
    ];
}

function camionGuardado(string $chasis, array $extra = []): int
{
    return DB::table('vehiculos')->insertGetId($extra + [
        'numero_chasis' => $chasis, 'marca' => 'Scania', 'modelo' => 'R450', 'anio' => 2020, 'tipo_vehiculo' => 'CAMION_TRACTO',
        'moneda_costo' => 'USD', 'costo_origen_moneda' => 60000, 'costo_origen_usd' => 60000, 'tasa_cambio_compra' => 1,
        'estado' => 'DISPONIBLE', 'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('el alta muestra las secciones, marcas existentes y un solo costo en dólares', function () {
    camionGuardado('EXISTE-1', ['marca' => 'VOLVO']);

    $this->get(route('vehicles.create'))
        ->assertOk()
        ->assertSeeInOrder(['Identificación del camión', 'Situación y origen', 'Costo y precios de venta', 'Fotos', 'Ficha técnica y sitio web'])
        ->assertSee('<option value="VOLVO">', false)
        ->assertSee('Costo de compra (USD)')
        ->assertDontSee('name="moneda_costo"', false)
        ->assertDontSee('name="tasa_cambio_compra"', false);
});

test('el costo se guarda siempre en dólares y el chasis en mayúsculas', function () {
    // Aunque llegue otra moneda, la compra de camiones es en USD.
    $this->post(route('vehicles.store'), altaCamion(['moneda_costo' => 'PYG', 'tasa_cambio_compra' => 6150]))->assertRedirect();

    $v = DB::table('vehiculos')->where('numero_chasis', 'YS2R4X20002118999')->first();
    expect($v)->not->toBeNull()
        ->and($v->moneda_costo)->toBe('USD')
        ->and((float) $v->costo_origen_usd)->toBe(60000.0)
        ->and((float) $v->costo_origen_moneda)->toBe(60000.0)
        ->and((float) $v->tasa_cambio_compra)->toBe(1.0);
});

test('sin costo se rechaza con un mensaje claro', function () {
    $datos = altaCamion();
    unset($datos['costo_origen_usd']);

    $this->post(route('vehicles.store'), $datos)
        ->assertSessionHasErrors(['costo_origen_usd' => 'Ingresá el costo de compra en dólares.']);
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

test('editar usa el mismo formulario: chasis fijo, costo en dólares y fotos con acciones', function () {
    $id = camionGuardado('EDIT-1');
    $img = DB::table('vehiculo_imagenes')->insertGetId([
        'vehiculo_id' => $id, 'ruta' => 'uploads/vehiculos/x.jpg', 'nombre_original' => 'x.jpg', 'es_portada' => false, 'orden' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->get(route('vehicles.edit', $id))
        ->assertOk()
        ->assertSeeInOrder(['Identificación del camión', 'Situación y origen', 'Costo y precios de venta', 'Fotos'])
        ->assertSee('value="EDIT-1" readonly', false)
        ->assertSee('name="costo_origen_usd" value="60000', false)
        ->assertSee('form="img-borrar-' . $img . '"', false)
        ->assertSee('form="img-portada-' . $img . '"', false)
        ->assertSee('Guardar cambios');
});

test('editar guarda el costo en dólares y el número de motor', function () {
    $id = camionGuardado('EDIT-2');

    $this->put(route('vehicles.update', $id), [
        'marca' => 'Scania', 'modelo' => 'R450', 'anio' => 2020, 'tipo_vehiculo' => 'CAMION_TRACTO', 'estado' => 'DISPONIBLE',
        'costo_origen_usd' => 58500, 'numero_motor' => 'DC13-123',
    ])->assertRedirect();

    $v = DB::table('vehiculos')->find($id);
    expect((float) $v->costo_origen_usd)->toBe(58500.0)
        ->and((float) $v->costo_origen_moneda)->toBe(58500.0)
        ->and($v->moneda_costo)->toBe('USD')
        ->and($v->numero_motor)->toBe('DC13-123');
});
