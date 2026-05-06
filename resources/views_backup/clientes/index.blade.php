@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>👥 Directorio de Clientes</h2>
                <a href="{{ route('clientes.create') }}" class="btn btn-primary" style="text-decoration:none">+ Nuevo
                    Cliente</a>
            </div>
            <p class="text-secondary">Gestiona el historial corporativo, datos de facturación y línea de crédito de tus
                compradores.</p>
        </div>
    </div>

    <div class="card mt-3">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>RUC/RUT</th>
                        <th>Razón Social</th>
                        <th>Contacto</th>
                        <th>Línea de Crédito</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $c)
                        <tr style="cursor:pointer" onclick="window.location='{{ route('clientes.show', $c->id) }}'">
                            <td>{{ $c->ruc ?: 'N/A' }}</td>
                            <td>
                                <a href="{{ route('clientes.show', $c->id) }}"
                                    style="color:var(--accent);text-decoration:none;font-weight:600">{{ $c->razon_social }}</a>
                                @if($c->nombre_fantasia)
                                    <br><span style="font-size:0.85rem; color:var(--text-muted)">{{ $c->nombre_fantasia }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $c->email ?: 'Sin email' }}<br>
                                <span
                                    style="font-size:0.85rem; color:var(--text-muted)">{{ $c->telefono ?: 'Sin teléfono' }}</span>
                            </td>
                            <td>
                                $ {{ number_format($c->linea_credito_usd, 2, ',', '.') }} USD
                            </td>
                            <td>
                                @if($c->activo)
                                    <span class="badge" style="background:var(--success);color:white">Activo</span>
                                @else
                                    <span class="badge" style="background:var(--danger);color:white">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:.5rem">
                                    <a href="{{ route('clientes.show', $c->id) }}" class="btn btn-ghost"
                                        title="Estado de Cuenta" style="padding:0.25rem 0.5rem">💳 Cuenta</a>
                                    <a href="{{ route('clientes.edit', $c->id) }}" class="btn btn-ghost" title="Editar"
                                        style="padding:0.25rem 0.5rem">✏️</a>
                                    <form action="{{ route('clientes.destroy', $c->id) }}" method="POST"
                                        onsubmit="return confirm('¿Eliminar y desactivar este cliente?')"
                                        style="display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost"
                                            style="color:var(--danger); padding:0.25rem 0.5rem" title="Eliminar">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 2rem;">No hay clientes registrados o activos en
                                la base de datos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection