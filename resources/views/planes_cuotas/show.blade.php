@extends('layouts.app')
@section('title', 'Detalle Plan de Cuotas')
@section('page-title', 'Plan de Pagos')

@section('content')
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="flash-success" style="background:rgba(59,130,246,.12);border-color:#3b82f6;color:#3b82f6">{{ session('info') }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver a la venta</a>
        <h2 class="text-base" style="color:var(--text-muted)">Plan {{ $plan->tipo_plan }} — Venta #{{ $venta->numero_venta ?? $venta->id }}</h2>
        <span class="badge-status {{ $plan->estado === 'COMPLETADO' ? 'badge-disponible' : ($plan->estado === 'CANCELADO' ? 'badge-vendido' : 'badge-preparacion') }}">{{ $plan->estado }}</span>
    </div>

    {{-- Resumen estadístico --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @php
            $stats = [
                'Cliente' => $cliente->razon_social ?? '—',
                'Capital Financiado' => '$ ' . number_format($plan->capital_total_usd, 2, ',', '.') . ' USD',
                'Cuotas Pagadas' => $pagado . ' de ' . $cuotas->count(),
                'Vencidas' => $vencidas,
            ];
        @endphp
        @foreach($stats as $l => $v)
            <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
                <div class="stat-label">{{ $l }}</div>
                <div class="font-bold text-accent text-base sm:text-lg">{{ $v }}</div>
            </div>
        @endforeach
    </div>

    {{-- Entregas iniciales --}}
    @if(isset($entregas) && $entregas->count() > 0)
        <div class="erp-card" style="margin-bottom:1.25rem">
            <div class="erp-card-header">
                <h2>💰 Entregas Iniciales</h2>
            </div>
            <div class="erp-card-body" style="padding:0">
                <div class="overflow-x-auto">
                <table class="erp-table">
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
        </div>
    @endif

    {{-- Grilla de cuotas --}}
    <div class="erp-card">
        <div class="erp-card-header">
            <h2>📅 Detalle de Cuotas</h2>
        </div>
        <div class="erp-card-body" style="padding:0">
            <div class="overflow-x-auto">
            <table class="erp-table">
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
                                    <button type="button" class="btn btn-ghost"
                                        style="padding:.25rem .5rem;font-size:.7rem;color:var(--success)"
                                        onclick="openPayModal({{ $c->id }}, {{ $c->numero_cuota }}, {{ $c->total_cuotas }}, '{{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}', {{ $c->capital }}, {{ $c->interes }}, {{ $c->capital + $c->interes }})">
                                        ✔ Pagar
                                    </button>
                                @elseif($c->estado === 'PAGADA')
                                    <div style="display:inline-flex;align-items:center;gap:.5rem">
                                        <span style="font-size:.75rem;color:var(--success)">{{ $c->fecha_pago_efectivo ? \Carbon\Carbon::parse($c->fecha_pago_efectivo)->format('d/m/Y') : '✔' }}</span>
                                        <a href="{{ route('cuotas.recibo-pdf', $c->id) }}" target="_blank"
                                           class="btn btn-ghost" style="padding:.2rem .4rem;font-size:.65rem;color:var(--text-muted)"
                                           title="Imprimir recibo PDF">🖨️</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Link to Client Dashboard --}}
    @if($cliente)
        <div style="margin-top:1.5rem">
            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-ghost">👤 Ver Estado de Cuenta del Cliente →</a>
        </div>
    @endif

    {{-- Modal de confirmación de pago --}}
    <div id="payModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:1rem;padding:1.5rem;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3)">
            <h3 style="margin:0 0 1rem;font-size:1.1rem;color:var(--text)">Confirmar Pago de Cuota</h3>
            <div style="background:var(--surface2);border-radius:.5rem;padding:1rem;margin-bottom:1rem;font-size:.85rem;color:var(--text-muted)">
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Cuota:</span>
                    <strong id="modalCuotaNum" style="color:var(--text)"></strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Vencimiento:</span>
                    <strong id="modalVencimiento" style="color:var(--text)"></strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Capital:</span>
                    <span id="modalCapital"></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Interés:</span>
                    <span id="modalInteres"></span>
                </div>
                <hr style="border-color:var(--border);margin:.5rem 0">
                <div style="display:flex;justify-content:space-between;font-size:.95rem">
                    <strong style="color:var(--text)">Total a pagar:</strong>
                    <strong id="modalTotal" style="color:var(--success)"></strong>
                </div>
            </div>
            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem">
                Se registrará el pago con fecha de hoy ({{ date('d/m/Y') }}). Esta acción no se puede deshacer.
            </p>
            <div style="display:flex;gap:.5rem;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" onclick="closePayModal()">Cancelar</button>
                <form id="payForm" method="POST" action=""
                    onsubmit="var b=this.querySelector('button[type=submit]');b.disabled=true;b.textContent='⏳ Procesando...';b.style.opacity='.6'">
                    @csrf
                    <input type="hidden" name="fecha_pago" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="monto_pagado" id="payFormMonto" value="">
                    <button type="submit" class="btn btn-primary" style="font-size:.85rem">Confirmar Pago</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function formatMoney(n) {
            return '$ ' + Number(n).toLocaleString('es-PY', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' USD';
        }
        function openPayModal(id, num, total, venc, capital, interes, monto) {
            document.getElementById('payForm').action = '/cuotas/' + id + '/pagar';
            document.getElementById('payFormMonto').value = monto;
            document.getElementById('modalCuotaNum').textContent = num + '/' + total;
            document.getElementById('modalVencimiento').textContent = venc;
            document.getElementById('modalCapital').textContent = formatMoney(capital);
            document.getElementById('modalInteres').textContent = formatMoney(interes);
            document.getElementById('modalTotal').textContent = formatMoney(monto);
            var m = document.getElementById('payModal');
            m.style.display = 'flex';
        }
        function closePayModal() {
            document.getElementById('payModal').style.display = 'none';
        }
        document.getElementById('payModal').addEventListener('click', function(e) {
            if (e.target === this) closePayModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePayModal();
        });
    </script>

    @if(session('show_print_cuota'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (confirm('¿Desea imprimir el recibo de pago ahora?')) {
                    window.open('{{ route('cuotas.recibo-pdf', session('show_print_cuota')) }}', '_blank');
                }
            });
        </script>
    @endif
@endsection