<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ProductionSeeder — Datos mínimos para arranque en producción.
 *
 * SOLO siembra:
 *   1. Usuario administrador inicial (con contraseña aleatoria segura)
 *   2. Configuración base de empresa (campos vacíos para completar desde /configuracion)
 *   3. Roles y permisos Spatie (delega a PermissionsSeeder)
 *   4. Cajas básicas (necesarias para el módulo de finanzas)
 *
 * NO siembra datos de demostración (clientes, vehículos, proveedores, etc.)
 *
 * Uso:
 *   php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Permisos y Roles (Spatie) ─────────────────────────────────
        $this->call(PermissionsSeeder::class);

        // ── 2. Usuario Administrador inicial ─────────────────────────────
        $email = env('ADMIN_EMAIL', 'admin@' . parse_url(env('APP_URL', 'localhost'), PHP_URL_HOST));
        $password = env('ADMIN_PASSWORD', Str::random(16));

        $adminId = DB::table('users')->insertGetId([
            'name'       => 'Administrador',
            'email'      => $email,
            'password'   => Hash::make($password),
            'role'       => 'admin',
            'activo'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asignar rol admin (Spatie)
        $adminUser = \App\Models\User::find($adminId);
        $adminUser?->assignRole('admin');

        // ── 3. Configuración de empresa (vacía — completar en /configuracion) ──
        DB::table('configuracion_empresa')->insertOrIgnore([
            [
                'id'              => 1,
                'nombre_empresa'  => env('APP_NAME', 'Mi Empresa'),
                'ruc'             => '',
                'telefono'        => '',
                'email'           => '',
                'direccion'       => '',
                'ciudad'          => 'Asunción',
                'pais'            => 'Paraguay',
                'moneda_base'     => 'USD',
                'prefijo_venta'   => 'V',
                'prefijo_factura' => 'F',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // ── 4. Cajas básicas ─────────────────────────────────────────────
        DB::table('cajas')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Caja Principal PYG', 'tipo' => 'CAJA_CHICA',  'moneda_principal' => 'PYG', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'Caja Principal USD', 'tipo' => 'CAJA_FUERTE', 'moneda_principal' => 'USD', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Resumen ───────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║        PRODUCCIÓN — DATOS INICIALES LISTOS       ║');
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->newLine();
        $this->command->warn('  ⚠️  GUARDAR ESTAS CREDENCIALES AHORA (no se muestran de nuevo):');
        $this->command->newLine();
        $this->command->info('  URL del sistema : ' . env('APP_URL'));
        $this->command->info('  Email admin     : ' . $email);
        $this->command->info('  Contraseña temp : ' . $password);
        $this->command->newLine();
        $this->command->warn('  🔒 Cambiá la contraseña en /usuarios después del primer login.');
        $this->command->warn('  🏢 Completá los datos de empresa en /configuracion.');
        $this->command->newLine();
    }
}
