@extends('layouts.app')
@section('title', 'Dashboard — ERP Camiones')
@section('page-title', '📊 Dashboard')

@section('content')
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-label">Vehículos en stock</div>
            <div class="stat-value">{{ $totalVehiculos }}</div>
            <div class="stat-icon">🚛</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Disponibles</div>
            <div class="stat-value">{{ $disponibles }}</div>
            <div class="stat-icon">✅</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">En preparación</div>
            <div class="stat-value">{{ $enPreparacion }}</div>
            <div class="stat-icon">🔧</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Ventas este mes</div>
            <div class="stat-value">{{ $ventasMes }}</div>
            <div class="stat-icon">💰</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>🚛 Últimos vehículos cargados</h2>
            <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">Ver todos →</a>
        </div>
        <div class="card-body" style="padding: 0">
            <table>
                <thead>
                    <tr>
                        <th>Chasis</th>
                        <th>Marca / Modelo</th>
                        <th>Año</th>
                        <th>Estado</th>
                        <th>Costo (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehiculos as $v)
                        <tr>
                            <td><code style="font-size:.75rem; color: var(--accent)">{{ $v->numero_chasis }}</code></td>
                            <td><strong>{{ $v->marca }}</strong> {{ $v->modelo }}</td>
                            <td>{{ $v->año }}</td>
                            <td>
                                @php
                                    $cls = match ($v->estado) {
                                        'DISPONIBLE' => 'badge-disponible',
                                        'EN_PREPARACION' => 'badge-preparacion',
                                        'TOMA' => 'badge-toma',
                                        default => 'badge-vendido',
                                    };
                                @endphp
                                <span class="badge-status {{ $cls }}">{{ $v->estado }}</span>
                            </td>
                            <td>$ {{ number_format($v->costo_origen_usd, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; color: var(--text-muted); padding: 2rem">Sin datos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection