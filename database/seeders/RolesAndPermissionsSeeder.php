<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea permisos en AMBOS idiomas (español para Web, inglés para API).
 *
 * Las rutas Web (routes/web.php) usan formato español: 'vehiculos.crear'.
 * Las rutas API (routes/api.php) usan formato inglés:  'vehicles.create'.
 *
 * Ambos guards: 'web' y 'sanctum'.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        // ── Permisos en ESPAÑOL (usados por routes/web.php) ───────────────
        $modulesEs = [
            'ventas'        => ['crear', 'ver', 'editar', 'eliminar'],
            'vehiculos'     => ['crear', 'ver', 'editar', 'eliminar'],
            'repuestos'     => ['crear', 'ver', 'editar', 'eliminar'],
            'clientes'      => ['crear', 'ver', 'editar', 'eliminar'],
            'proveedores'   => ['crear', 'ver', 'editar', 'eliminar'],
            'facturas'      => ['crear', 'ver', 'editar', 'eliminar'],
            'cuotas'        => ['ver', 'pagar', 'liquidar', 'descuento'],
            'finanzas'      => ['ver', 'transferir', 'arquear'],
            'documentos'    => ['crear', 'ver', 'eliminar'],
            'configuracion' => ['ver', 'editar'],
            'roles'         => ['crear', 'ver', 'editar', 'eliminar'],
            'usuarios'      => ['crear', 'ver', 'editar', 'eliminar'],
            'reportes'      => ['ver', 'exportar'],
            'auditoria'     => ['ver'],
        ];

        // ── Permisos en INGLÉS (usados por routes/api.php) ────────────────
        $modulesEn = [
            'sales'        => ['view', 'create', 'update', 'delete', 'cancel', 'update.completed'],
            'vehicles'     => ['view', 'create', 'update', 'delete', 'update.sold', 'expenses.create'],
            'purchases'    => ['view', 'create', 'update', 'delete', 'cancel', 'update.paid'],
            'clientes'     => ['view', 'create', 'update', 'delete', 'credit.update'],
            'installments' => ['view', 'pay', 'liquidate', 'discount', 'delete'],
            'reports'      => ['view', 'export'],
            'cash'         => ['view', 'transfer', 'reconcile'],
        ];

        // Crear ambos en ambos guards (web + sanctum)
        $allNames = [];
        foreach (['web', 'sanctum'] as $guard) {
            foreach ([$modulesEs, $modulesEn] as $modules) {
                foreach ($modules as $module => $actions) {
                    foreach ($actions as $action) {
                        $name = "{$module}.{$action}";
                        Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
                        $allNames[$name] = true;
                    }
                }
            }
        }

        // ───────────────────────────────────────────────────────────────────
        // ROLES
        // ───────────────────────────────────────────────────────────────────
        foreach (['web', 'sanctum'] as $guard) {

            // Super Admin — todo
            $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);
            $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

            // Admin — todo excepto users
            $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
            $admin->syncPermissions(
                Permission::where('guard_name', $guard)
                    ->where('name', 'not like', 'usuarios.%')
                    ->where('name', 'not like', 'users.%')
                    ->get()
            );

            // Gerente — operativo + reportes + finanzas
            $gerentePerms = [
                'ventas.crear','ventas.ver','ventas.editar',
                'vehiculos.crear','vehiculos.ver','vehiculos.editar',
                'repuestos.crear','repuestos.ver','repuestos.editar',
                'clientes.crear','clientes.ver','clientes.editar',
                'proveedores.crear','proveedores.ver','proveedores.editar',
                'facturas.crear','facturas.ver',
                'cuotas.ver','cuotas.pagar','cuotas.liquidar','cuotas.descuento',
                'finanzas.ver','finanzas.transferir','finanzas.arquear',
                'documentos.crear','documentos.ver','documentos.eliminar',
                'configuracion.ver',
                'reportes.ver','reportes.exportar',
                'auditoria.ver',
                // API equivalents
                'sales.view','sales.create','sales.update','sales.cancel',
                'vehicles.view','vehicles.create','vehicles.update',
                'purchases.view','purchases.create','purchases.update',
                'installments.view','installments.pay','installments.liquidate','installments.discount',
                'reports.view','reports.export',
                'cash.view','cash.transfer','cash.reconcile',
            ];
            $gerente = Role::firstOrCreate(['name' => 'gerente', 'guard_name' => $guard]);
            $gerente->syncPermissions(Permission::whereIn('name', $gerentePerms)->where('guard_name', $guard)->get());

            // Vendedor — operaciones propias
            $vendedorPerms = [
                'ventas.crear','ventas.ver',
                'vehiculos.ver',
                'repuestos.ver',
                'clientes.crear','clientes.ver','clientes.editar',
                'cuotas.ver',
                'cotizaciones.ver','cotizaciones.crear',
                'documentos.crear','documentos.ver',
                'reportes.ver',
                'sales.view','sales.create','sales.update',
                'vehicles.view',
                'clientes.view','clientes.create','clientes.update',
                'installments.view',
                'reports.view',
            ];
            $vendedor = Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => $guard]);
            $vendedor->syncPermissions(Permission::whereIn('name', $vendedorPerms)->where('guard_name', $guard)->get());

            // Cobrador
            $cobradorPerms = [
                'ventas.ver',
                'clientes.ver',
                'cuotas.ver','cuotas.pagar',
                'documentos.ver',
                'reportes.ver',
                'finanzas.ver',
                'sales.view',
                'clientes.view',
                'installments.view','installments.pay',
                'reports.view',
                'cash.view',
            ];
            $cobrador = Role::firstOrCreate(['name' => 'cobrador', 'guard_name' => $guard]);
            $cobrador->syncPermissions(Permission::whereIn('name', $cobradorPerms)->where('guard_name', $guard)->get());

            // Mecánico
            $mecanicoPerms = [
                'vehiculos.ver',
                'repuestos.ver',
                'documentos.ver',
                'vehicles.view','vehicles.expenses.create',
            ];
            $mecanico = Role::firstOrCreate(['name' => 'mecanico', 'guard_name' => $guard]);
            $mecanico->syncPermissions(Permission::whereIn('name', $mecanicoPerms)->where('guard_name', $guard)->get());

            // Compras
            $comprasPerms = [
                'repuestos.crear','repuestos.ver','repuestos.editar',
                'proveedores.crear','proveedores.ver','proveedores.editar',
                'facturas.crear','facturas.ver',
                'documentos.crear','documentos.ver',
                'purchases.view','purchases.create','purchases.update',
                'vehicles.view',
            ];
            $compras = Role::firstOrCreate(['name' => 'compras', 'guard_name' => $guard]);
            $compras->syncPermissions(Permission::whereIn('name', $comprasPerms)->where('guard_name', $guard)->get());
        }

        Artisan::call('permission:cache-reset');
    }
}
