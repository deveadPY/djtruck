@extends('layouts.app')
@section('title', 'Repuestos')
@section('page-title', '🔧 Repuestos / Stock')

@section('content')
    @if(session('success'))
        <div
            style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    ✅ {{ session('success') }}</div>@endif

    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
        <a href="{{ route('repuestos.create') }}" class="btn btn-primary">+ Nuevo repuesto</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Inventario de repuestos</h2>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Marca compatible</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Stock mín.</th>
                        <th>Costo USD</th>
                        <th>Precio venta USD</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repuestos as $r)
                        <tr>
                            <td><code style="color:var(--accent);font-size:.78rem">{{ $r->codigo }}</code></td>
                            <td>{{ $r->descripcion }}</td>
                            <td>{{ $r->marca_compatible ?? '—' }}</td>
                            <td>{{ $r->unidad_medida }}</td>
                            <td>
                                <span
                                    style="color:{{ $r->stock_actual <= $r->stock_minimo ? 'var(--danger)' : 'var(--success)' }};font-weight:600">
                                    {{ number_format($r->stock_actual, 2, ',', '.') }}
                                </span>
                            </td>
                            <td>{{ number_format($r->stock_minimo, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($r->costo_promedio_usd, 2, ',', '.') }}</td>
                            <td>{{ $r->precio_venta_usd ? '$ ' . number_format($r->precio_venta_usd, 2, ',', '.') : '—' }}</td>
                            <td>
                                <a href="{{ route('repuestos.edit', $r->id) }}" class="btn btn-ghost"
                                    style="padding:.3rem .6rem;font-size:.75rem">✏️</a>
                                <form method="POST" action="{{ route('repuestos.destroy', $r->id) }}" style="display:inline"
                                    onsubmit="return confirm('¿Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost"
                                        style="padding:.3rem .6rem;font-size:.75rem;color:var(--danger)">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;color:var(--text-muted);padding:2rem">Sin repuestos
                                registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection