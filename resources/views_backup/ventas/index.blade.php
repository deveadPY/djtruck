@extends('layouts.app')
@section('title', 'Ventas')
@section('page-title', '💰 Ventas')

@section('content')
@if(session('success'))<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">✅ {{ session('success') }}</div>@endif

<div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
    <a href="{{ route('ventas.create') }}" class="btn btn-primary">+ Nueva venta</a>
</div>

<div class="card">
    <div class="card-header"><h2>Historial de ventas</h2></div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>N° Venta</th>
                    <th>Fecha</th>
                    <th>Vehículo</th>
                    <th>Cliente</th>
                    <th>Precio Venta</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventas as $v)
                <tr>
                    <td><a href="{{ route('ventas.show', $v->id) }}" style="color:var(--accent);text-decoration:none;font-weight:600">{{ $v->numero_venta }}</a></td>
                    <td>{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}</td>
                    <td><strong>{{ $v->marca }}</strong> {{ $v->modelo }}</td>
                    <td>{{ $v->cliente_nombre }}</td>
                    <td>
                        <strong style="color:var(--accent);font-size:.95rem">$ {{ number_format($v->precio_venta_usd, 2, ',', '.') }} USD</strong>
                        @if($v->moneda_venta !== 'USD')
                        <br><span style="font-size:.78rem;color:var(--text-muted)">{{ number_format($v->precio_venta_moneda ?? 0, 2, ',', '.') }} {{ $v->moneda_venta }}</span>
                        @endif
                    </td>
                    <td>
                        @php $cls = match($v->estado){
                            'COMPLETADO'=>'badge-disponible','CANCELADO'=>'badge-vendido',
                            'RESERVADO'=>'badge-preparacion',default=>'badge-toma'
                        }; @endphp
                        <span class="badge-status {{ $cls }}">{{ $v->estado }}</span>
                    </td>
                    <td><a href="{{ route('ventas.show', $v->id) }}" class="btn btn-ghost" style="padding:.3rem .6rem;font-size:.75rem">Ver →</a></td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem">Sin ventas registradas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
