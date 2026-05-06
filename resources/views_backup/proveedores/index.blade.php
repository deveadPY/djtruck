@extends('layouts.app')
@section('title', 'Proveedores')
@section('page-title', '🏷️ Proveedores')

@section('content')
    @if(session('success'))
        <div
            style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    ✅ {{ session('success') }}</div>@endif

    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
        <a href="{{ route('proveedores.create') }}" class="btn btn-primary">+ Nuevo Proveedor</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Directorio de Proveedores</h2>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Razón Social</th>
                        <th>RUC / RUT</th>
                        <th>Tipo</th>
                        <th>País</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proveedores as $p)
                        <tr>
                            <td>
                                <a href="{{ route('proveedores.show', $p->id) }}"
                                    style="color:var(--text); text-decoration:underline; font-weight:600">
                                    {{ $p->razon_social }}
                                </a><br>
                                <small style="color:var(--text-muted)">{{ $p->nombre_fantasia }}</small>
                            </td>
                            <td>{{ $p->ruc_rut_nit ?? '—' }}</td>
                            <td><span class="badge-status badge-disponible">{{ $p->tipo }}</span></td>
                            <td>{{ $p->pais }}</td>
                            <td>{{ $p->telefono ?? '—' }}</td>
                            <td style="display:flex; gap:.2rem; align-items:center">
                                <a href="{{ route('proveedores.show', $p->id) }}" class="btn btn-ghost"
                                    style="padding:.3rem .6rem;font-size:.75rem">Ver →</a>
                                <a href="{{ route('proveedores.edit', $p->id) }}" class="btn btn-ghost"
                                    style="padding:.3rem .6rem;font-size:.75rem">✏️</a>
                                <form method="POST" action="{{ route('proveedores.destroy', $p->id) }}" style="display:inline"
                                    onsubmit="return confirm('¿Eliminar proveedor?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost"
                                        style="padding:.3rem .6rem;font-size:.75rem;color:var(--danger)">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">No hay proveedores
                                registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection