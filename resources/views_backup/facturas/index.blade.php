@extends('layouts.app')
@section('title', 'Facturas y Gastos')
@section('page-title', '💸 Facturas y Gastos')

@section('content')
    @if(session('success'))
        <div
            style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    ✅ {{ session('success') }}</div>@endif

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem">
        <form method="GET" action="{{ route('facturas.index') }}"
            style="display:flex; gap:1rem; align-items:flex-end; background:var(--surface); padding:1rem; border-radius:10px; border:1px solid var(--border)">
            <div>
                <label
                    style="display:block; font-size:.75rem; color:var(--text-muted); margin-bottom:.25rem">Proveedor</label>
                <select name="proveedor_id" class="form-control" style="min-width:200px">
                    <option value="">Todos los proveedores</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                            {{ $prov->razon_social }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; font-size:.75rem; color:var(--text-muted); margin-bottom:.25rem">Desde</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-control">
            </div>
            <div>
                <label style="display:block; font-size:.75rem; color:var(--text-muted); margin-bottom:.25rem">Hasta</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-control">
            </div>
            <div style="display:flex; gap:.5rem">
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                @if(request()->hasAny(['proveedor_id', 'fecha_inicio', 'fecha_fin']))
                    <a href="{{ route('facturas.index') }}" class="btn btn-ghost" style="padding:.5rem 1rem">Limpiar</a>
                @endif
            </div>
        </form>
        <a href="{{ route('facturas.create') }}" class="btn btn-primary">+ Cargar Factura / Gasto</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Registro global de gastos</h2>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>N° Factura</th>
                        <th>Proveedor</th>
                        <th>Destino</th>
                        <th>Referencia</th>
                        <th>Total USD</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facturas as $f)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($f->fecha_factura)->format('d/m/Y') }}</td>
                            <td><strong>{{ $f->numero_factura }}</strong></td>
                            <td>
                                <a href="{{ route('proveedores.show', $f->proveedor_id) }}"
                                    style="color:var(--text); text-decoration:underline; font-weight:500">
                                    {{ $f->razon_social }}
                                </a>
                            </td>
                            <td>
                                @if($f->destino === 'VEHICULO')
                                    <span style="color:var(--accent);font-weight:600">🚛 Vehículo</span>
                                @else
                                    <span style="color:var(--text-muted);font-weight:600">🏢 Local</span>
                                @endif
                            </td>
                            <td>
                                @if($f->destino === 'VEHICULO' && $f->vehiculo_id)
                                    {{ $f->marca }} {{ $f->modelo }} (Chasis: {{ substr($f->numero_chasis, -6) }})
                                @else
                                    {{ $f->cuenta_gasto ?? '—' }}
                                @endif
                            </td>
                            <td style="color:var(--danger);font-weight:600">$ {{ number_format($f->total_usd, 2, ',', '.') }}
                            </td>
                            <td>
                                @php $cls = match ($f->estado) { 'APROBADA', 'PAGADA' => 'badge-disponible', 'ANULADA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                <span class="badge-status {{ $cls }}">{{ $f->estado }}</span>
                            </td>
                            <td><a href="{{ route('facturas.show', $f->id) }}" class="btn btn-ghost"
                                    style="padding:.3rem .6rem;font-size:.75rem">Ver →</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem">No hay facturas
                                registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection