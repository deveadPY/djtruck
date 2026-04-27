@extends('layouts.app')
@section('title', 'Cotizaciones')
@section('page-title', '💱 Cotizaciones y Monedas')

@section('content')
    @if(session('success'))
        <div
            style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    ✅ {{ session('success') }}</div>@endif

    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
        <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary">+ Actualizar Cotización</a>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2>Historial de Cotizaciones (Diario)</h2>
                <div style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem">Base: <strong>Dólar Estadounidense
                        (USD)</strong></div>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Moneda</th>
                        <th>Venta (Usado para ERP)</th>
                        <th>Compra</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cotizaciones as $c)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}</td>
                            <td>
                                @if($c->moneda_destino === 'PYG')
                                    <span class="badge-status badge-preparacion">PYG - Guaraní</span>
                                @else
                                    <span class="badge-status badge-disponible">BRL - Real</span>
                                @endif
                            </td>
                            <td><strong style="color:var(--accent)">{{ number_format($c->venta, 2, ',', '.') }}</strong></td>
                            <td style="color:var(--text-muted)">{{ number_format($c->compra, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem">Aún no hay
                                cotizaciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection