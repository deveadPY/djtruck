<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Definir todos los permisos ────────────────────────────────────
        $permissions = [
            // Vehículos
            'vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar', 'vehiculos.eliminar',
            // Ventas
            'ventas.ver', 'ventas.crear', 'ventas.editar',
            // Clientes
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
            // Proveedores
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar', 'proveedores.eliminar',
            // Repuestos
            'repuestos.ver', 'repuestos.crear', 'repuestos.editar', 'repuestos.eliminar',
            // Facturas proveedor
            'facturas.ver', 'facturas.crear',
            // Cuotas / Planes
            'cuotas.ver', 'cuotas.pagar', 'cuotas.editar',
            // Finanzas / Cajas
            'finanzas.ver', 'finanzas.crear', 'finanzas.editar', 'finanzas.reconciliar',
            // Reportes
            'reportes.ver',
            // Documentos
            'documentos.ver', 'documentos.crear', 'documentos.eliminar',
            // Configuración empresa
            'configuracion.ver', 'configuracion.editar',
            // Gestión de usuarios
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            // Gestión de roles
            'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar',
            // Email / Notificaciones
            'email.ver', 'email.editar', 'email.enviar',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Crear roles con sus permisos ─────────────────────────────────

        // ADMIN — acceso total
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        // GERENTE — todo excepto gestión de roles y eliminar usuarios
        $gerente = Role::firstOrCreate(['name' => 'gerente', 'guard_name' => 'web']);
        $gerente->syncPermissions(array_filter($permissions, fn($p) =>
            !in_array($p, [
                'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar',
                'usuarios.eliminar',
                'configuracion.editar',
            ])
        ));

        // VENDEDOR — ventas, vehículos (ver), clientes, cuotas
        $vendedor = Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        $vendedor->syncPermissions([
            'vehiculos.ver',
            'ventas.ver', 'ventas.crear',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'cuotas.ver', 'cuotas.pagar',
            'documentos.ver', 'documentos.crear',
            'reportes.ver',
            'email.enviar',
        ]);

        // CAJERO — finanzas, cuotas, consulta ventas/clientes
        $cajero = Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'web']);
        $cajero->syncPermissions([
            'finanzas.ver', 'finanzas.crear', 'finanzas.editar', 'finanzas.reconciliar',
            'cuotas.ver', 'cuotas.pagar',
            'ventas.ver',
            'clientes.ver',
            'reportes.ver',
            'email.enviar',
        ]);

        // ── Asignar roles Spatie a usuarios existentes ────────────────────
        // Guard: only run if users table exists and has data
        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
            $roleMap = [
                'admin'    => 'admin',
                'gerente'  => 'gerente',
                'vendedor' => 'vendedor',
                'cajero'   => 'cajero',
            ];

            foreach (User::withTrashed()->get() as $user) {
                $roleName = $roleMap[$user->role] ?? null;
                if ($roleName && !$user->hasRole($roleName)) {
                    $user->assignRole($roleName);
                }
            }
        }

        $this->command->info('  ✅ Permisos y roles configurados.');
    }
}
