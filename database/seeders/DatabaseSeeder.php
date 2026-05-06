<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuarios ─────────────────────────────────────────────────────
        DB::table('users')->insert([
            ['name' => 'Administrador', 'email' => 'admin@erp.com', 'password' => Hash::make('admin123'), 'role' => 'admin', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gerente', 'email' => 'gerente@erp.com', 'password' => Hash::make('gerente123'), 'role' => 'gerente', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Vendedor Demo', 'email' => 'ventas@erp.com', 'password' => Hash::make('ventas123'), 'role' => 'vendedor', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cajero Demo', 'email' => 'caja@erp.com', 'password' => Hash::make('caja123'), 'role' => 'cajero', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Cajas ────────────────────────────────────────────────────────
        DB::table('cajas')->insert([
            ['nombre' => 'Caja Chica PYG', 'tipo' => 'CAJA_CHICA', 'moneda_principal' => 'PYG', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Caja Fuerte USD', 'tipo' => 'CAJA_FUERTE', 'moneda_principal' => 'USD', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco Itaú PY', 'tipo' => 'BANCO', 'moneda_principal' => 'PYG', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco BCP USD', 'tipo' => 'BANCO', 'moneda_principal' => 'USD', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Proveedores demo ─────────────────────────────────────────────
        DB::table('proveedores')->insert([
            ['ruc_rut_nit' => '80100000-1', 'razon_social' => 'TRUCKS IMPORT SA', 'pais' => 'PY', 'tipo' => 'IMPORTADOR', 'moneda_principal' => 'USD', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ruc_rut_nit' => '80200000-5', 'razon_social' => 'MERCEDES-BENZ PARAGUAY', 'pais' => 'PY', 'tipo' => 'DISTRIBUIDOR', 'moneda_principal' => 'USD', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ruc_rut_nit' => '12345678', 'razon_social' => 'VOLVO DO BRASIL LTDA', 'pais' => 'BR', 'tipo' => 'FABRICANTE', 'moneda_principal' => 'BRL', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Clientes demo ────────────────────────────────────────────────
        DB::table('clientes')->insert([
            [
                'ruc' => '3500000-1', 'razon_social' => 'TRANSPORTES RODRIGUEZ SA',
                'pais' => 'PY', 'telefono' => '0981-555-100',
                'email' => 'compras@transportesrodriguez.com.py',
                'linea_credito_usd' => 150000.00,
                'activo' => true, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'ruc' => '4200000-8', 'razon_social' => 'AGRO EXPORTACIONES CHACO SRL',
                'pais' => 'PY', 'telefono' => '0971-444-200',
                'email' => 'gerencia@agrochaco.com.py',
                'linea_credito_usd' => 80000.00,
                'activo' => true, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'ruc' => '5100000-3', 'razon_social' => 'CONSTRUCTORA PAVIMENTO SA',
                'pais' => 'PY', 'telefono' => '021-555-300',
                'email' => 'logistica@pavimento.com.py',
                'linea_credito_usd' => 200000.00,
                'activo' => true, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'ruc' => '6200000-5', 'razon_social' => 'LOGÍSTICA NORTE SA',
                'pais' => 'PY', 'telefono' => '0991-333-400',
                'email' => 'compras@logisticanorte.com.py',
                'linea_credito_usd' => 0,
                'activo' => true, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'ruc' => '22345678', 'razon_social' => 'AGRO TRANSPORTE BRASIL LTDA',
                'pais' => 'BR', 'telefono' => '+55-11-9999-0000',
                'email' => 'compras@agrobrasil.com.br',
                'linea_credito_usd' => 50000.00,
                'activo' => true, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        // ── Vehículos demo ────────────────────────────────────────────────
        $vehiculos = [
            [
                'numero_chasis' => '9BM384075PB295123',
                'marca' => 'Mercedes-Benz',
                'modelo' => 'Actros 2651',
                'anio' => 2024,
                'color' => 'Blanco',
                'tipo_vehiculo' => 'CAMION_TRACTO',
                'kilometraje' => 0,
                'estado' => 'DISPONIBLE',
                'moneda_costo' => 'USD',
                'costo_origen_usd' => 85000.00,
                'costo_origen_moneda' => 85000.00,
                'total_gastos_usd' => 3200.00,
                'proveedor_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1,
            ],
            [
                'numero_chasis' => 'YFLGAV1B59V400001',
                'marca' => 'Volvo',
                'modelo' => 'FH 460',
                'anio' => 2023,
                'color' => 'Gris',
                'tipo_vehiculo' => 'CAMION_TRACTO',
                'kilometraje' => 0,
                'estado' => 'EN_PREPARACION',
                'moneda_costo' => 'BRL',
                'costo_origen_usd' => 78000.00,
                'costo_origen_moneda' => 394000.00,
                'tasa_cambio_compra' => 5.05,
                'total_gastos_usd' => 0,
                'proveedor_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1,
            ],
            [
                'numero_chasis' => 'XL9HAN8XWDM000001',
                'marca' => 'Scania',
                'modelo' => 'R450',
                'anio' => 2022,
                'color' => 'Azul',
                'tipo_vehiculo' => 'CAMION_TRACTO',
                'kilometraje' => 45000,
                'estado' => 'TOMA',
                'moneda_costo' => 'USD',
                'costo_origen_usd' => 55000.00,
                'costo_origen_moneda' => 55000.00,
                'valor_toma_usd' => 55000.00,
                'total_gastos_usd' => 0,
                'venta_canje_origen_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1,
            ],
        ];

        foreach ($vehiculos as $vehiculo) {
            DB::table('vehiculos')->insert($vehiculo);
        }

        // ── Repuestos demo ────────────────────────────────────────────────
        DB::table('stock_repuestos')->insert([
            ['codigo' => 'FLT-001', 'descripcion' => 'Filtro de aceite MB', 'marca_compatible' => 'Mercedes-Benz', 'unidad_medida' => 'UND', 'stock_actual' => 10, 'stock_minimo' => 3, 'costo_promedio_usd' => 35.00, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'FLT-002', 'descripcion' => 'Filtro de combustible MB', 'marca_compatible' => 'Mercedes-Benz', 'unidad_medida' => 'UND', 'stock_actual' => 8, 'stock_minimo' => 3, 'costo_promedio_usd' => 45.00, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'NEU-295', 'descripcion' => 'Neumático 295/80 R22.5', 'marca_compatible' => NULL, 'unidad_medida' => 'UND', 'stock_actual' => 20, 'stock_minimo' => 6, 'costo_promedio_usd' => 380.00, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'ACE-15W40', 'descripcion' => 'Aceite Motor 15W40 20L', 'marca_compatible' => NULL, 'unidad_medida' => 'LTS', 'stock_actual' => 200, 'stock_minimo' => 40, 'costo_promedio_usd' => 4.50, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Configuración de empresa ─────────────────────────────────────
        DB::table('configuracion_empresa')->insertOrIgnore([
            [
                'id'             => 1,
                'nombre_empresa' => 'ERP Camiones & Repuestos',
                'ruc'            => '80000000-1',
                'telefono'       => '+595 21 123456',
                'email'          => 'info@erpcamiones.com.py',
                'direccion'      => 'Av. Aviadores del Chaco 1234',
                'ciudad'         => 'Asunción',
                'pais'           => 'Paraguay',
                'moneda_base'    => 'USD',
                'prefijo_venta'  => 'V',
                'prefijo_factura' => 'F',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        // ── Permisos y Roles (Spatie) ────────────────────────────────────
        $this->call(PermissionsSeeder::class);

        // ── Plantillas de Email ───────────────────────────────────────────
        $this->call(EmailPlantillasSeeder::class);

        $this->command->info('✅ Datos iniciales sembrados correctamente.');
        $this->command->info('');
        $this->command->info('CREDENCIALES DE ACCESO:');
        $this->command->info('  admin@erp.com   / admin123');
        $this->command->info('  ventas@erp.com  / ventas123');
    }
}
