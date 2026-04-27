@extends('layouts.app')
@section('title', 'Detalle Venta')
@section('page-title', '💰 Detalle de Venta')

@section('content')
    @if(session('success'))
        <div
            style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    ✅ {{ session('success') }}</div>@endif

    <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem">
        <a href="{{ route('ventas.index') }}" class="btn btn-ghost">← Volver</a>
        <h2 style="font-size:1rem;color:var(--text-muted)">Venta {{ $venta->numero_venta }}</h2>
        @php $cls = match ($venta->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO' => 'badge-vendido', 'RESERVADO' => 'badge-preparacion', default => 'badge-toma'}; @endphp
        <span class="badge-status {{ $cls }}">{{ $venta->estado }}</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem">
        <div class="card">
            <div class="card-header">
                <h2>🚛 Vehículo</h2>
            </div>
            <div class="card-body">
                @foreach(['Chasis' => $venta->vehiculo->numero_chasis ?? '—', 'Marca/Modelo' => ($venta->vehiculo->marca ?? '') . ' ' . ($venta->vehiculo->modelo ?? ''), 'Año' => $venta->vehiculo->año ?? '—'] as $l => $v)
                    <div
                        style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.875rem">
                        <span style="color:var(--text-muted)">{{ $l }}</span><span>{{ $v }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>👤 Cliente</h2>
            </div>
            <div class="card-body">
                @foreach(['Razón social' => $venta->cliente->razon_social ?? '—', 'RUC' => $venta->cliente->ruc ?? '—', 'Teléfono' => $venta->cliente->telefono ?? '—'] as $l => $v)
                    <div
                        style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.875rem">
                        <span style="color:var(--text-muted)">{{ $l }}</span><span>{{ $v }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        <div class="card-header">
            <h2>💵 Resumen financiero</h2>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
            @php
                $items = ['Precio venta' => '$' . number_format($venta->precio_venta_usd, 2, ',', '.') . ' USD', 'Moneda' => $venta->moneda_venta, 'Valor libro (snapshot)' => '$' . number_format($venta->valor_libro_snapshot, 2, ',', '.') . ' USD', 'Rentabilidad' => ($rentabilidad >= 0 ? '✅ ' : '⚠️ ') . '$' . number_format($rentabilidad, 2, ',', '.') . ' USD'];
            @endphp
            @foreach($items as $l => $v)
                <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem">
                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">{{ $l }}
                    </div>
                    <div
                        style="font-weight:700;color:{{ str_contains($l, 'Rent') ? ($rentabilidad >= 0 ? 'var(--success)' : 'var(--danger)') : 'var(--accent)' }}">
                        {{ $v }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($pagos->where('tipo_pago', '!=', 'PLAN_CUOTAS')->count() > 0)
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
                <h2>💰 Entregas / Pagos Iniciales</h2>
            </div>
            <div class="card-body" style="padding:0">
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Monto USD</th>
                            <th>Fecha</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pagos->where('tipo_pago', '!=', 'PLAN_CUOTAS') as $p)
                            <tr>
                                <td>{{ $p->tipo_pago }}</td>
                                <td>$ {{ number_format($p->monto_usd, 2, ',', '.') }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') }}</td>
                                <td>{{ $p->referencia_bancaria ?: $p->observaciones ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(!$plan)
        <div style="margin-bottom:1.25rem">
            <a href="{{ route('planes_cuotas.create', $venta->id) }}" class="btn btn-primary">📅 Crear Plan de Cuotas</a>
        </div>
    @else
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
                <h2>📅 Plan de Cuotas — {{ $plan->tipo_plan }}</h2>
                <a href="{{ route('planes_cuotas.show', $plan->id) }}" class="btn btn-ghost">Ver detalle →</a>
            </div>
            <div class="card-body" style="padding:0">
                <table>
                    <thead>
                        <tr>
                            <th>Cuota</th>
                            <th>Vencimiento</th>
                            <th>Capital</th>
                            <th>Interés</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cuotas->take(5) as $c)
                            <tr>
                                <td>{{ $c->numero_cuota }}/{{ $c->total_cuotas }}</td>
                                <td>{{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}</td>
                                <td>$ {{ number_format($c->capital, 2, ',', '.') }}</td>
                                <td>$ {{ number_format($c->interes, 2, ',', '.') }}</td>
                                <td>
                                    @php $cs = match ($c->estado) { 'PAGADA' => 'badge-disponible', 'VENCIDA', 'EN_MORA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                    <span class="badge-status {{ $cs }}">{{ $c->estado }}</span>
                                </td>
                                <td>
                                    @if($c->estado === 'PENDIENTE')
                                        <form method="POST" action="{{ route('cuotas.pagar', $c->id) }}"
                                            style="display:inline-flex;gap:.25rem">
                                            @csrf
                                            <input type="hidden" name="fecha_pago" value="{{ date('Y-m-d') }}">
                                            <input type="hidden" name="monto_pagado" value="{{ $c->capital + $c->interes }}">
                                            <button class="btn btn-ghost"
                                                style="padding:.25rem .5rem;font-size:.7rem;color:var(--success)">✔ Pagar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if($cuotas->count() > 5)
                            <tr>
                                <td colspan="6" style="text-align:center;padding:.75rem;font-size:.8rem;color:var(--text-muted)"><a
                                        href="{{ route('planes_cuotas.show', $plan->id) }}" style="color:var(--accent)">Ver las
                                        {{ $cuotas->count() - 5 }} cuotas restantes →</a></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @include('partials.documentos', [
        'documentos' => $documentos ?? collect(),
        'documentableType' => 'ventas',
        'documentableId' => $venta->id,
    ])
@endsection