<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\Http\Requests\StoreUserRequest;
use App\Infrastructure\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');

        $query = User::withTrashed()
            ->with('roles');

        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        return view('usuarios.index', compact('users', 'q'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('usuarios.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'activo'   => $validated['activo'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$user->name}' creado correctamente.");
    }

    public function edit(User $usuario)
    {
        $roles = Role::orderBy('name')->get();
        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $usuario)
    {
        $validated = $request->validated();

        $data = [
            'name'   => $validated['name'],
            'email'  => $validated['email'],
            'role'   => $validated['role'],
            'activo' => $validated['activo'] ?? $usuario->activo,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $usuario->update($data);
        $usuario->syncRoles([$validated['role']]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$usuario->name}' actualizado.");
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No podés eliminar tu propio usuario.');
        }

        $usuario->delete();
        return redirect()->route('usuarios.index')
            ->with('success', "Usuario desactivado.");
    }

    public function toggleActivo(User $usuario)
    {
        if ($usuario->trashed()) {
            $usuario->restore();
            $usuario->update(['activo' => true]);
            $msg = "Usuario '{$usuario->name}' reactivado.";
        } else {
            $usuario->update(['activo' => !$usuario->activo]);
            $msg = "Estado del usuario '{$usuario->name}' actualizado.";
        }

        return redirect()->route('usuarios.index')->with('success', $msg);
    }
}
