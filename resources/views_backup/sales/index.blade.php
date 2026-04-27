@extends('layouts.app')
@section('title', 'Ventas — ERP Camiones')
@section('page-title', '💰 Registro de Ventas')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2>Historial de ventas</h2>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Vehículo</th>
                        <th>Cliente</th>
                        <th>Moneda</th>
                        <th>Precio venta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $v)
                        <tr>
                            <td><a href="{{ route('sales.show', $v->id) }}"
                                    style="color:var(--accent); text-decoration:none">{{ $v->id }}</a></td>
                            <td>{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y') }}</td>
                            <td>{{ $v->vehiculo->marca ?? '—' }} {{ $v->vehiculo->modelo ?? '' }}</td>
                            <td>{{ $v->cliente->razon_social ?? '—' }}</td>
                            <td>{{ $v->moneda_venta ?? '—' }}</td>
                            <td>$ {{ number_format($v->precio_venta_usd ?? 0, 2, ',', '.') }}</td>
                            <td><span class="badge-status badge-disponible">{{ $v->estado ?? 'COMPLETADA' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; color:var(--text-muted); padding:2rem">Sin ventas
                                registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection