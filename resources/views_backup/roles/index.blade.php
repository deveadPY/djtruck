@extends('layouts.app')

@section('title', 'Roles del Sistema')
@section('page-title', '🔑 Roles y Permisos')

@include('partials.form-styles')

@section('content')

@if(session('success'))
<div class="flash-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="flash-error" style="margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <div>
        <p style="color:#aaa;font-size:.9rem;margin:0;">{{ $roles->count() }} roles definidos en el sistema</p>
    </div>
    @can('roles.crear')
    <a href="{{ route('roles.create') }}" class="btn btn-primary" style="padding:.5rem 1.2rem;">+ Nuevo Rol</a>
    @endcan
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #2a2d3e;">
                    <th style="padding:.8rem 1rem;text-align:left;color:#aaa;font-size:.8rem;font-weight:600;text-transform:uppercase;">Rol</th>
                    <th style="padding:.8rem 1rem;text-align:center;color:#aaa;font-size:.8rem;font-weight:600;text-transform:uppercase;">Usuarios</th>
                    <th style="padding:.8rem 1rem;text-align:center;color:#aaa;font-size:.8rem;font-weight:600;text-transform:uppercase;">Permisos</th>
                    <th style="padding:.8rem 1rem;text-align:right;color:#aaa;font-size:.8rem;font-weight:600;text-transform:uppercase;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $rol)
                <tr style="border-bottom:1px solid #1e2130;">
                    <td style="padding:.8rem 1rem;">
                        <span style="font-weight:600;color:#e2e8f0;">{{ ucfirst($rol->name) }}</span>
                    </td>
                    <td style="padding:.8rem 1rem;text-align:center;">
                        <span class="badge-status" style="background:#1e2130;color:#aaa;">{{ $rol->users_count }}</span>
                    </td>
                    <td style="padding:.8rem 1rem;text-align:center;">
                        <span class="badge-status" style="background:#1e2130;color:#6c63ff;">{{ $rol->permissions_count }}</span>
                    </td>
                    <td style="padding:.8rem 1rem;text-align:right;">
                        @can('roles.editar')
                        <a href="{{ route('roles.edit', $rol) }}" style="color:#6c63ff;text-decoration:none;font-size:.85rem;margin-right:.8rem;">Editar permisos</a>
                        @endcan
                        @can('roles.eliminar')
                        @if($rol->users_count == 0)
                        <form method="POST" action="{{ route('roles.destroy', $rol) }}" style="display:inline;"
                              onsubmit="return confirm('¿Eliminar el rol {{ $rol->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:.85rem;">Eliminar</button>
                        </form>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="padding:2rem;text-align:center;color:#aaa;">No hay roles definidos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
