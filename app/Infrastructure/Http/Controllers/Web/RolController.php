<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    // Agrupación de permisos por módulo para la matriz de la UI
    private const MODULES = [
        'vehiculos'     => ['ver', 'crear', 'editar', 'eliminar'],
        'ventas'        => ['ver', 'crear', 'editar'],
        'clientes'      => ['ver', 'crear', 'editar', 'eliminar'],
        'proveedores'   => ['ver', 'crear', 'editar', 'eliminar'],
        'repuestos'     => ['ver', 'crear', 'editar', 'eliminar'],
        'facturas'      => ['ver', 'crear'],
        'cuotas'        => ['ver', 'pagar', 'editar'],
        'finanzas'      => ['ver', 'crear', 'editar', 'reconciliar'],
        'documentos'    => ['ver', 'crear', 'eliminar'],
        'reportes'      => ['ver'],
        'configuracion' => ['ver', 'editar'],
        'usuarios'      => ['ver', 'crear', 'editar', 'eliminar'],
        'roles'         => ['ver', 'crear', 'editar', 'eliminar'],
    ];

    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $modules   = self::MODULES;
        $allActions = ['ver', 'crear', 'editar', 'eliminar', 'pagar', 'reconciliar'];
        $rolePerms = collect();
        return view('roles.create', compact('modules', 'allActions', 'rolePerms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:50|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', "Rol '{$role->name}' creado correctamente.");
    }

    public function edit(Role $role)
    {
        $modules    = self::MODULES;
        $allActions = ['ver', 'crear', 'editar', 'eliminar', 'pagar', 'reconciliar'];
        $rolePerms  = $role->permissions->pluck('name');
        return view('roles.edit', compact('role', 'modules', 'allActions', 'rolePerms'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => 'required|string|max:50|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.index')
            ->with('success', "Rol '{$role->name}' actualizado.");
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', "No se puede eliminar el rol '{$role->name}' porque tiene usuarios asignados.");
        }

        $role->delete();
        return redirect()->route('roles.index')
            ->with('success', "Rol eliminado.");
    }
}
