@extends('layouts.app')
@section('title', 'Detalle Factura')
@section('page-title', '💸 Detalle de Factura / Gasto')

@section('content')
<div style="margin-bottom:1rem"><a href="{{ route('facturas.index') }}" class="btn btn-ghost">← Volver</a></div>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:1rem">
            <h2>Factura N° {{ $factura->numero_factura }}</h2>
            @php $cls=match($factura->estado){'APROBADA','PAGADA'=>'badge-disponible','ANULADA'=>'badge-vendido',default=>'badge-preparacion'}; @endphp
            <span class="badge-status {{ $cls }}">{{ $factura->estado }}</span>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
            <div>
                <h3 style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem;text-transform:uppercase">Datos del Gasto</h3>
                @foreach([
                    'Fecha'=>$factura->fecha_factura,
                    'Proveedor'=>$factura->proveedor->razon_social ?? '—',
                    'Destino'=>$factura->destino,
                    'Concepto'=>$factura->cuenta_gasto ?? '—'
                ] as $l=>$v)
                <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)">
                    <span style="color:var(--text-muted)">{{ $l }}</span><strong>{{ $v }}</strong>
                </div>
                @endforeach

                @if($factura->destino === 'VEHICULO' && isset($factura->vehiculo))
                <div style="margin-top:1.5rem;background:var(--surface2);padding:1rem;border-radius:10px;border:1px solid var(--border)">
                    <div style="font-size:.8rem;color:var(--text-muted);font-weight:600;margin-bottom:.5rem">CAMIÓN ASOCIADO</div>
                    <a href="{{ route('vehicles.show', $factura->vehiculo->id) }}" style="color:var(--accent);text-decoration:none;font-weight:600;display:block;margin-bottom:.25rem">
                        {{ $factura->vehiculo->marca }} {{ $factura->vehiculo->modelo }}
                    </a>
                    <div style="font-size:.8rem">Chasis: {{ $factura->vehiculo->numero_chasis }}</div>
                    <div style="font-size:.75rem;margin-top:.5rem;color:var(--text-muted)">
                        ⚠️ Este gasto ya está sumado al valor libro del vehículo.
                    </div>
                </div>
                @endif
            </div>

            <div>
                <h3 style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem;text-transform:uppercase">Importes</h3>
                @foreach([
                    'Subtotal'=>$factura->moneda.' '.number_format($factura->subtotal,2,',','.'),
                    'Impuestos'=>$factura->moneda.' '.number_format($factura->impuestos,2,',','.'),
                ] as $l=>$v)
                <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)">
                    <span style="color:var(--text-muted)">{{ $l }}</span><span>{{ $v }}</span>
                </div>
                @endforeach
                <div style="display:flex;justify-content:space-between;padding:.75rem 0;margin-top:.5rem;border-top:2px solid var(--border);font-size:1.1rem">
                    <span style="color:var(--text-muted);font-weight:600">Total equivalente</span>
                    <strong style="color:var(--danger)">USD {{ number_format($factura->total_usd,2,',','.') }}</strong>
                </div>

                @if($factura->descripcion)
                <div style="margin-top:2rem">
                    <h3 style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem;text-transform:uppercase">Observaciones</h3>
                    <p style="font-size:.875rem;color:var(--text);line-height:1.5">{{ $factura->descripcion }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('partials.documentos', [
    'documentos' => $documentos ?? collect(),
    'documentableType' => 'facturas_proveedores',
    'documentableId' => $factura->id,
])
@endsection
