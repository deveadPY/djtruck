@extends('layouts.app')
@section('title', 'Detalle Venta — ERP Camiones')
@section('page-title', '💰 Detalle de Venta')

@section('content')
    <div style="margin-bottom:1rem">
        <a href="{{ route('sales.index') }}" class="btn btn-ghost">← Volver</a>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Venta #{{ $venta->id }}</h2>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem">
                <div>
                    <h3 style="font-size:.8rem; color:var(--text-muted); margin-bottom:1rem; text-transform:uppercase">Datos
                        generales</h3>
                    @foreach([
                            'Fecha' => \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y H:i'),
                            'Vendedor' => $venta->vendedor->name ?? '—',
                            'Cliente' => $venta->cliente->razon_social ?? '—',
                            'Vehículo' => ($venta->vehiculo->marca ?? '—') . ' ' . ($venta->vehiculo->modelo ?? ''),
                        ] as $label => $value)
                        <div style="display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid var(--border); font-size:.875rem">
                            <span style="color:var(--text-muted)">{{ $label }}</span>
                            <span>{{ $value }}</span>

                                            </div>
                    @endforeach
                </div>
                <div>
                    <h3 style="font-size:.8rem; color:var(--text-muted); margin-bottom:1rem; text-transform:uppercase">Financiero</h3>
                    @foreach([
                            'Moneda venta' => $venta->moneda_venta ?? '—',
                            'Precio venta' => '$ ' . number_format($venta->precio_venta_usd ?? 0, 2, ',', '.'),
                            'Costo libro' => '$ ' . number_format($venta->costo_libro_usd ?? 0, 2, ',', '.'),
                            'Rentabilidad' => '$ ' . number_format(($venta->precio_venta_usd ?? 0) - ($venta->costo_libro_usd ?? 0), 2, ',', '.'),
                        ] as $label => $value)
                        <div style="display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid var(--border); font-size:.875rem">
                            <span style="color:var(--text-muted)">{{ $label }}</span>
                            <span style="color:var(--accent)">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
