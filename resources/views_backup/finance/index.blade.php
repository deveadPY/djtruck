@extends('layouts.app')
@section('title', 'Finanzas — ERP Camiones')
@section('page-title', '🏦 Panel Financiero')

@section('content')
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Cajas activas</div>
            <div class="stat-value">{{ $totalCajas }}</div>
            <div class="stat-icon">🏦</div>
        </div>
        <div class="stat-card primary">
            <div class="stat-label">Cuotas vencidas</div>
            <div class="stat-value">{{ $cuotasVencidas }}</div>
            <div class="stat-icon">⚠️</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>💼 Cajas registradas</h2>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Moneda</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cajas as $c)
                        <tr>
                            <td>{{ $c->nombre }}</td>
                            <td>{{ $c->tipo }}</td>
                            <td>{{ $c->moneda_principal }}</td>
                            <td><span
                                    class="badge-status {{ $c->activo ? 'badge-disponible' : 'badge-vendido' }}">{{ $c->activo ? 'Activa' : 'Inactiva' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color:var(--text-muted); padding:2rem">Sin cajas
                                registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection