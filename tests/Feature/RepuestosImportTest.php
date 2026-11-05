<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['repuestos.ver', 'repuestos.crear'] as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    // 'deposito' no está entre los roles que obligan a configurar 2FA.
    Role::firstOrCreate(['name' => 'deposito', 'guard_name' => 'web'])
        ->syncPermissions(['repuestos.ver', 'repuestos.crear']);

    $this->user = User::factory()->create();
    $this->user->assignRole('deposito');
});

/** Genera un .xlsx real en disco para ejercitar el lector de phpspreadsheet. */
function xlsxDeRepuestos(array $filas): UploadedFile
{
    $hoja = (new Spreadsheet())->getActiveSheet();
    $hoja->fromArray(array_merge([['codigo', 'descripcion', 'precio_venta_usd']], $filas));

    $ruta = tempnam(sys_get_temp_dir(), 'repuestos') . '.xlsx';
    (new Xlsx($hoja->getParent()))->save($ruta);

    return new UploadedFile($ruta, 'repuestos.xlsx', null, null, true);
}

test('importar un excel crea los repuestos nuevos con stock en cero', function () {
    $this->actingAs($this->user)
        ->post('/repuestos/import', ['archivo' => xlsxDeRepuestos([
            ['FILTRO-001', 'Filtro de aceite', 25.50],
            ['CORREA-002', 'Correa de distribución', 80],
        ])])
        ->assertRedirect(route('repuestos.index'))
        ->assertSessionHas('success');

    expect(DB::table('stock_repuestos')->count())->toBe(2);

    $filtro = DB::table('stock_repuestos')->where('codigo', 'FILTRO-001')->first();
    expect($filtro->descripcion)->toBe('Filtro de aceite')
        ->and((float) $filtro->precio_venta_usd)->toBe(25.50)
        ->and((float) $filtro->stock_actual)->toBe(0.0);
});

test('importar no toca el stock de un repuesto que ya existe', function () {
    DB::table('stock_repuestos')->insert([
        'codigo' => 'FILTRO-001', 'descripcion' => 'Viejo',
        'stock_actual' => 7, 'precio_venta_usd' => 10,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->post('/repuestos/import', ['archivo' => xlsxDeRepuestos([['FILTRO-001', 'Filtro nuevo', 33]])])
        ->assertRedirect(route('repuestos.index'));

    $filtro = DB::table('stock_repuestos')->where('codigo', 'FILTRO-001')->first();
    expect(DB::table('stock_repuestos')->count())->toBe(1)
        ->and($filtro->descripcion)->toBe('Filtro nuevo')
        ->and((float) $filtro->precio_venta_usd)->toBe(33.0)
        ->and((float) $filtro->stock_actual)->toBe(7.0);
});

test('solo se aceptan planillas', function () {
    $this->actingAs($this->user)
        ->from('/repuestos')
        ->post('/repuestos/import', ['archivo' => UploadedFile::fake()->create('virus.php', 10)])
        ->assertSessionHasErrors('archivo');

    expect(DB::table('stock_repuestos')->count())->toBe(0);
});

test('un usuario sin permiso no puede importar', function () {
    $this->actingAs(User::factory()->create())
        ->post('/repuestos/import', ['archivo' => xlsxDeRepuestos([['X-1', 'Algo', 1]])])
        ->assertForbidden();

    expect(DB::table('stock_repuestos')->count())->toBe(0);
});
