<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
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

    $this->vehiculoId = DB::table('vehiculos')->insertGetId([
        'numero_chasis' => 'GASTO-TEST-01', 'marca' => 'Volvo', 'modelo' => 'FH', 'anio' => 2020,
        'estado' => 'DISPONIBLE', 'created_at' => now(), 'updated_at' => now(),
    ]);
});

afterEach(function () {
    foreach (DB::table('documentos')->where('documentable_type', 'gastos_vehiculo')->pluck('ruta') as $ruta) {
        @unlink(public_path($ruta));
        @rmdir(dirname(public_path($ruta)));
    }
});

function gastoPayload(array $extra = []): array
{
    return $extra + [
        'concepto' => 'Cambio de frenos', 'categoria' => 'REPARACION_MECANICA', 'origen_tipo' => 'MANO_OBRA',
        'moneda' => 'PYG', 'tasa_cambio' => 7300, 'monto_moneda' => 730000, 'monto_usd' => 100,
        'fecha_gasto' => '2026-11-11',
    ];
}

test('registra gasto con comprobante y lo muestra en el detalle', function () {
    $this->post(route('gastos.store', $this->vehiculoId), gastoPayload([
        'comprobante' => UploadedFile::fake()->create('factura.pdf', 200, 'application/pdf'),
    ]))->assertRedirect(route('vehicles.show', $this->vehiculoId));

    $gasto = DB::table('gastos_vehiculo')->where('vehiculo_id', $this->vehiculoId)->first();
    expect((float) $gasto->tasa_cambio)->toBe(7300.0);

    $doc = DB::table('documentos')->where('documentable_type', 'gastos_vehiculo')->where('documentable_id', $gasto->id)->first();
    expect($doc)->not->toBeNull()
        ->and($doc->nombre_original)->toBe('factura.pdf')
        ->and(file_exists(public_path($doc->ruta)))->toBeTrue();

    expect((float) DB::table('vehiculos')->where('id', $this->vehiculoId)->value('total_gastos_usd'))->toBe(100.0);

    $this->get(route('vehicles.show', $this->vehiculoId))
        ->assertOk()
        ->assertSee(route('documentos.download', [$doc->id, 'inline' => 1]), false);

    $this->get(route('documentos.download', [$doc->id, 'inline' => 1]))->assertOk();
});

test('registra gasto sin comprobante', function () {
    $this->post(route('gastos.store', $this->vehiculoId), gastoPayload())->assertRedirect();

    expect(DB::table('gastos_vehiculo')->count())->toBe(1)
        ->and(DB::table('documentos')->count())->toBe(0);
});

test('rechaza comprobante con formato no permitido', function () {
    $this->post(route('gastos.store', $this->vehiculoId), gastoPayload([
        'comprobante' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
    ]))->assertSessionHasErrors('comprobante');

    expect(DB::table('gastos_vehiculo')->count())->toBe(0);
});
