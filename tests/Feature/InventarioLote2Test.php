<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permisos = ['vehiculos.ver', 'vehiculos.editar', 'documentos.ver'];
    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // Rol no privilegiado: evita el redirect obligatorio a configurar 2FA.
    Role::firstOrCreate(['name' => 'taller', 'guard_name' => 'web'])->syncPermissions($permisos);

    $this->user = User::factory()->create(['activo' => true]);
    $this->user->assignRole('taller');
    $this->actingAs($this->user);
});

function vehiculoLote2(array $datos): int
{
    return DB::table('vehiculos')->insertGetId($datos + [
        'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2020, 'estado' => 'DISPONIBLE',
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('el listado ordena por precio y año e ignora órdenes fuera de la lista blanca', function () {
    vehiculoLote2(['numero_chasis' => 'CHASIS-CARO', 'precio_contado_usd' => 90000, 'anio' => 2015, 'created_at' => now()->subDays(40)]);
    vehiculoLote2(['numero_chasis' => 'CHASIS-BARATO', 'precio_contado_usd' => 30000, 'anio' => 2022, 'created_at' => now()->subDays(5)]);

    $this->get(route('vehicles.index', ['orden' => 'precio_asc']))->assertOk()->assertSeeInOrder(['CHASIS-BARATO', 'CHASIS-CARO']);
    $this->get(route('vehicles.index', ['orden' => 'precio_desc']))->assertOk()->assertSeeInOrder(['CHASIS-CARO', 'CHASIS-BARATO']);
    $this->get(route('vehicles.index', ['orden' => 'anio_desc']))->assertOk()->assertSeeInOrder(['CHASIS-BARATO', 'CHASIS-CARO']);
    $this->get(route('vehicles.index', ['orden' => 'antiguos']))->assertOk()->assertSeeInOrder(['CHASIS-CARO', 'CHASIS-BARATO']);

    // Valor no permitido (posible inyección): cae en "recientes".
    $this->get(route('vehicles.index', ['orden' => 'id; DROP TABLE vehiculos']))
        ->assertOk()
        ->assertSeeInOrder(['CHASIS-BARATO', 'CHASIS-CARO'])
        ->assertViewHas('orden', 'recientes');
});

test('el listado conserva el orden en la paginación', function () {
    foreach (range(1, 16) as $i) {
        vehiculoLote2(['numero_chasis' => "PAG-$i"]);
    }

    $this->get(route('vehicles.index', ['orden' => 'anio_desc', 'q' => 'PAG']))
        ->assertOk()
        ->assertSee('orden=anio_desc', false)
        ->assertSee('q=PAG', false);
});

test('el listado muestra la portada, el placeholder y los días en stock', function () {
    $conFoto = vehiculoLote2(['numero_chasis' => 'CON-FOTO', 'created_at' => now()->subDays(12)]);
    vehiculoLote2(['numero_chasis' => 'SIN-FOTO']);
    vehiculoLote2(['numero_chasis' => 'YA-VENDIDO', 'estado' => 'VENDIDO', 'created_at' => now()->subDays(300)]);

    DB::table('vehiculo_imagenes')->insert([
        ['vehiculo_id' => $conFoto, 'ruta' => 'uploads/vehiculos/borrada.jpg', 'orden' => 0, 'es_portada' => true, 'deleted_at' => now()],
        ['vehiculo_id' => $conFoto, 'ruta' => 'uploads/vehiculos/segunda.jpg', 'orden' => 1, 'es_portada' => false, 'deleted_at' => null],
        ['vehiculo_id' => $conFoto, 'ruta' => 'uploads/vehiculos/portada.jpg', 'orden' => 2, 'es_portada' => true, 'deleted_at' => null],
    ]);

    $res = $this->get(route('vehicles.index', ['estado' => 'TODOS']))->assertOk();
    $res->assertSee(asset('uploads/vehiculos/portada.jpg'), false)
        ->assertDontSee('borrada.jpg')
        ->assertSee('loading="lazy"', false)
        ->assertSee('aria-label="Sin foto"', false)
        ->assertSee('Días en stock')
        ->assertSee('12 días')
        ->assertDontSee('300 días');
});

test('la ficha muestra margen estimado, motor, ubicación y días en stock', function () {
    $id = vehiculoLote2([
        'numero_chasis' => 'FICHA-01', 'costo_origen_usd' => 50000, 'total_gastos_usd' => 5000,
        'precio_contado_usd' => 60000, 'motor_descripcion' => 'D13K 500', 'ubicacion' => 'Patio Asunción',
        'created_at' => now()->subDays(3),
    ]);

    $this->get(route('vehicles.show', $id))
        ->assertOk()
        ->assertSee('Margen estimado')
        ->assertSee('$ 5.000,00')
        ->assertSee('text-green-500', false)
        ->assertSee('D13K 500')
        ->assertSee('Patio Asunción')
        ->assertSee('3 días');

    DB::table('vehiculos')->where('id', $id)->update(['precio_contado_usd' => 52000]);
    $this->get(route('vehicles.show', $id))->assertOk()->assertSee('−$ 3.000,00')->assertSee('text-red-500', false);
});

test('guardar y registrar otro vuelve al formulario conservando moneda y categoría', function () {
    $id = vehiculoLote2(['numero_chasis' => 'GASTO-OTRO']);
    $payload = [
        'concepto' => 'Flete puerto', 'categoria' => 'LOGISTICA', 'origen_tipo' => 'FLETE',
        'moneda' => 'PYG', 'tasa_cambio' => 7300, 'monto_moneda' => 730000, 'fecha_gasto' => '2026-11-11',
    ];

    $this->post(route('gastos.store', $id), $payload + ['otro' => '1'])
        ->assertRedirect(route('gastos.create', $id))
        ->assertSessionHas('success')
        ->assertSessionHas('gasto_moneda', 'PYG')
        ->assertSessionHas('gasto_categoria', 'LOGISTICA');

    $this->get(route('gastos.create', $id))
        ->assertOk()
        ->assertSee('Guardar y registrar otro')
        ->assertSee('value="PYG" class="peer sr-only" checked', false)
        ->assertSee('value="LOGISTICA" selected', false);

    // El botón principal sigue yendo a la ficha.
    $this->post(route('gastos.store', $id), $payload)->assertRedirect(route('vehicles.show', $id));
    expect(DB::table('gastos_vehiculo')->where('vehiculo_id', $id)->count())->toBe(2);
});
