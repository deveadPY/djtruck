@extends('layouts.app')
@section('title', 'Vehículos — ERP Camiones')
@section('page-title', '🚛 Inventario de Vehículos')

@section('content')
    <div class="card">
        <div class="card-header" style="justify-content:space-between">
            <h2>Lista completa</h2>
            <a href="{{ route('vehicles.create') }}" class="btn btn-primary" style="padding:.4rem 1rem">+ Nuevo Vehículo</a>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Chasis</th>
                        <th>Marca / Modelo</th>
                        <th>Año</th>
                        <th>Km</th>
                        <th>Costo (USD)</th>
                        <th>Precio Venta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehiculos as $v)
                        <tr>
                            <td><a href="{{ route('vehicles.show', $v->id) }}"
                                    style="color:var(--accent); text-decoration:none; font-size:.78rem">{{ $v->numero_chasis }}</a>
                            </td>
                            <td><strong>{{ $v->marca }}</strong> {{ $v->modelo }}</td>
                            <td>{{ $v->año }}</td>
                            <td>{{ number_format($v->kilometraje, 2, ',', '.') }}</td>
                            <td>
                                <span>$
                                    {{ number_format($v->costo_origen_usd + ($v->total_gastos_usd ?? 0), 2, ',', '.') }}</span>
                                <br><span style="font-size:.72rem;color:var(--text-muted)">Costo: $
                                    {{ number_format($v->costo_origen_usd, 2, ',', '.') }}</span>
                            </td>
                            <td>
                                @if($v->venta_precio_usd)
                                    <strong style="color:var(--success)">$ {{ number_format($v->venta_precio_usd, 2, ',', '.') }}
                                        USD</strong>
                                    @if($v->venta_moneda && $v->venta_moneda !== 'USD')
                                        <br><span
                                            style="font-size:.72rem;color:var(--text-muted)">{{ number_format($v->venta_precio_moneda ?? 0, 2, ',', '.') }}
                                            {{ $v->venta_moneda }}</span>
                                    @endif
                                @elseif($v->precio_venta_sugerido_usd)
                                    <span style="color:var(--warning)">$
                                        {{ number_format($v->precio_venta_sugerido_usd, 2, ',', '.') }}</span>
                                    <br><span style="font-size:.72rem;color:var(--text-muted)">Sugerido</span>
                                @else
                                    <span style="color:var(--text-muted);font-size:.8rem">Sin venta</span>
                                @endif
                            </td>
                            <td>
                                @php $cls = match ($v->estado) { 'DISPONIBLE' => 'badge-disponible', 'EN_PREPARACION' => 'badge-preparacion', 'TOMA' => 'badge-toma', default => 'badge-vendido'}; @endphp
                                <span class="badge-status {{ $cls }}">{{ $v->estado }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; color:var(--text-muted); padding:2rem">Sin vehículos
                                registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection