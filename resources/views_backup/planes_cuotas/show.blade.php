@extends('layouts.app')
@section('title', 'Detalle Plan de Cuotas')
@section('page-title', '📅 Plan de Pagos')

@section('content')
    @if(session('success'))
        <div
            style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    ✅ {{ session('success') }}</div>@endif

    <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem">
        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver a la venta</a>
        <h2 style="font-size:1rem;color:var(--text-muted)">Plan {{ $plan->tipo_plan }} — Venta
            #{{ $venta->numero_venta ?? $venta->id }}</h2>
        <span
            class="badge-status {{ $plan->estado === 'COMPLETADO' ? 'badge-disponible' : ($plan->estado === 'CANCELADO' ? 'badge-vendido' : 'badge-preparacion') }}">{{ $plan->estado }}</span>
    </div>

    {{-- Resumen estadístico --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
        @php
            $stats = [
                'Cliente' => $cliente->razon_social ?? '—',
                'Capital Financiado' => '$ ' . number_format($plan->capital_total_usd, 2, ',', '.') . ' USD',
                'Cuotas Pagadas' => $pagado . ' de ' . $cuotas->count(),
                'Vencidas' => $vencidas,
            ];
        @endphp
        @foreach($stats as $l => $v)
            <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem">
                <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">{{ $l }}
                </div>
                <div style="font-weight:700;color:var(--accent)">{{ $v }}</div>
            </div>
        @endforeach
    </div>

    {{-- Entregas iniciales --}}
    @if(isset($entregas) && $entregas->count() > 0)
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
                <h2>💰 Entregas Iniciales</h2>
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
                        @foreach($entregas as $e)
                            <tr>
                                <td>{{ $e->tipo_pago }}</td>
                                <td>$ {{ number_format($e->monto_usd, 2, ',', '.') }}</td>
                                <td>{{ \Carbon\Carbon::parse($e->fecha_pago)->format('d/m/Y') }}</td>
                                <td>{{ $e->referencia_bancaria ?: $e->observaciones ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Grilla de cuotas --}}
    <div class="card">
        <div class="card-header">
            <h2>📅 Detalle de Cuotas</h2>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Cuota</th>
                        <th>Vencimiento</th>
                        <th>Capital</th>
                        <th>Interés</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cuotas as $c)
                        <tr>
                            <td>{{ $c->numero_cuota }}/{{ $c->total_cuotas }}</td>
                            <td>{{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}</td>
                            <td>$ {{ number_format($c->capital, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($c->interes, 2, ',', '.') }}</td>
                            <td><strong>$ {{ number_format($c->capital + $c->interes, 2, ',', '.') }}</strong></td>
                            <td>$ {{ number_format($c->monto_pagado, 2, ',', '.') }}</td>
                            <td>
                                @php $cs = match ($c->estado) { 'PAGADA' => 'badge-disponible', 'VENCIDA', 'EN_MORA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                <span class="badge-status {{ $cs }}">{{ $c->estado }}</span>
                            </td>
                            <td>
                                @if($c->estado === 'PENDIENTE' || $c->estado === 'VENCIDA' || $c->estado === 'EN_MORA')
                                    <form method="POST" action="{{ route('cuotas.pagar', $c->id) }}"
                                        style="display:inline-flex;gap:.25rem">
                                        @csrf
                                        <input type="hidden" name="fecha_pago" value="{{ date('Y-m-d') }}">
                                        <input type="hidden" name="monto_pagado" value="{{ $c->capital + $c->interes }}">
                                        <button class="btn btn-ghost"
                                            style="padding:.25rem .5rem;font-size:.7rem;color:var(--success)">✔ Pagar</button>
                                    </form>
                                @elseif($c->estado === 'PAGADA')
                                    <div style="display:inline-flex;align-items:center;gap:.5rem">
                                        <span style="font-size:.75rem;color:var(--success)">{{ $c->fecha_pago_efectivo ? \Carbon\Carbon::parse($c->fecha_pago_efectivo)->format('d/m/Y') : '✔' }}</span>
                                        <a href="{{ route('cuotas.recibo-pdf', $c->id) }}" target="_blank"
                                           class="btn btn-ghost" style="padding:.2rem .4rem;font-size:.65rem;color:var(--text-muted)"
                                           title="Descargar recibo PDF">📄</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Link to Client Dashboard --}}
    @if($cliente)
        <div style="margin-top:1.5rem">
            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-ghost">👤 Ver Estado de Cuenta del Cliente →</a>
        </div>
    @endif
@endsection