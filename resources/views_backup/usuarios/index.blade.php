@extends('layouts.app')

@section('title', 'Gestión de Usuarios')
@section('page-title', '👥 Gestión de Usuarios')

@include('partials.form-styles')

@section('content')

@if(session('success'))
<div class="flash-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="flash-error" style="margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <p style="color:#aaa;font-size:.9rem;margin:0;">{{ $users->whereNull('deleted_at')->count() }} usuarios activos</p>
    @can('usuarios.crear')
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary" style="padding:.5rem 1.2rem;">+ Nuevo Usuario</a>
    @endcan
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #2a2d3e;">
                    <th style="padding:.8rem 1rem;text-align:left;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;">Usuario</th>
                    <th style="padding:.8rem 1rem;text-align:left;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;">Email</th>
                    <th style="padding:.8rem 1rem;text-align:center;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;">Rol</th>
                    <th style="padding:.8rem 1rem;text-align:center;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;">Estado</th>
                    <th style="padding:.8rem 1rem;text-align:right;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php $esEliminado = $user->trashed(); $esInactivo = !$user->activo; @endphp
                <tr style="border-bottom:1px solid #1e2130;{{ $esEliminado ? 'opacity:.5;' : '' }}">
                    <td style="padding:.8rem 1rem;">
                        <div style="display:flex;align-items:center;gap:.7rem;">
                            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#00d4aa);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;">
                                {{ strtoupper(substr($user->name,0,1)) }}
                            </div>
                            <span style="font-weight:600;color:#e2e8f0;">{{ $user->name }}</span>
                            @if($user->id === auth()->id())
                            <span style="font-size:.7rem;background:#1e3a5f;color:#60a5fa;padding:.1rem .4rem;border-radius:4px;">Tú</span>
                            @endif
                        </div>
                    </td>
                    <td style="padding:.8rem 1rem;color:#aaa;font-size:.9rem;">{{ $user->email }}</td>
                    <td style="padding:.8rem 1rem;text-align:center;">
                        @php
                            $role = $user->roles->first()?->name ?? $user->role ?? '—';
                            $roleColors = ['admin'=>'#6c63ff','gerente'=>'#00d4aa','vendedor'=>'#f59e0b','cajero'=>'#10b981'];
                            $color = $roleColors[$role] ?? '#aaa';
                        @endphp
                        <span style="font-size:.8rem;font-weight:600;color:{{ $color }};background:{{ $color }}22;padding:.2rem .7rem;border-radius:12px;border:1px solid {{ $color }}44;">
                            {{ ucfirst($role) }}
                        </span>
                    </td>
                    <td style="padding:.8rem 1rem;text-align:center;">
                        @if($esEliminado)
                        <span style="font-size:.8rem;color:#dc2626;background:#dc262622;padding:.2rem .7rem;border-radius:12px;">Eliminado</span>
                        @elseif($esInactivo)
                        <span style="font-size:.8rem;color:#f59e0b;background:#f59e0b22;padding:.2rem .7rem;border-radius:12px;">Inactivo</span>
                        @else
                        <span style="font-size:.8rem;color:#10b981;background:#10b98122;padding:.2rem .7rem;border-radius:12px;">Activo</span>
                        @endif
                    </td>
                    <td style="padding:.8rem 1rem;text-align:right;">
                        @can('usuarios.editar')
                        @if(!$esEliminado)
                        <a href="{{ route('usuarios.edit', $user) }}" style="color:#6c63ff;text-decoration:none;font-size:.85rem;margin-right:.8rem;">Editar</a>
                        @endif
                        @endcan

                        @can('usuarios.editar')
                        <form method="POST" action="{{ route('usuarios.toggle', $user) }}" style="display:inline;">
                            @csrf
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.85rem;color:{{ $esEliminado || $esInactivo ? '#10b981' : '#f59e0b' }};">
                                {{ $esEliminado ? 'Restaurar' : ($esInactivo ? 'Activar' : 'Desactivar') }}
                            </button>
                        </form>
                        @endcan

                        @can('usuarios.eliminar')
                        @if(!$esEliminado && $user->id !== auth()->id())
                        <form method="POST" action="{{ route('usuarios.destroy', $user) }}" style="display:inline;"
                              onsubmit="return confirm('¿Eliminar usuario {{ $user->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:.85rem;margin-left:.5rem;">Eliminar</button>
                        </form>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:2rem;text-align:center;color:#aaa;">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
