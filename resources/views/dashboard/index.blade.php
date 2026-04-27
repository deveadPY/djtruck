@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

    {{-- ── Alertas críticas ─────────────────────────────────────────── --}}


    @if($cuotasHoy > 0)
        <div class="mb-4 px-4 py-3 rounded-xl border text-sm flex items-center gap-3"
             style="background:#6c63ff15;border-color:#6c63ff40;color:#6c63ff;">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
            </svg>
            <span><strong>{{ $cuotasHoy }} cuota(s) vencen hoy.</strong> Verificar cobros del día.</span>
        </div>
    @endif

    {{-- ── Stats row 1 ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="stat-card primary">
            <div class="stat-label">Vehículos en stock</div>
            <div class="stat-value">{{ $totalVehiculos }}</div>
            <div class="text-xs mt-1" style="color:var(--text-muted)">{{ $disponibles }} disponibles</div>
            <div class="stat-icon"><svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">En preparación</div>
            <div class="stat-value">{{ $enPreparacion }}</div>
            <div class="text-xs mt-1" style="color:var(--text-muted)">pendiente entrega</div>
            <div class="stat-icon"><svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Ventas este mes</div>
            <div class="stat-value">{{ $ventasMes }}</div>
            <div class="text-xs mt-1" style="color:var(--accent)">$ {{ number_format($ingresosUsdMes, 0, ',', '.') }} USD</div>
            <div class="stat-icon"><svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Cuotas en mora</div>
            <div class="stat-value">{{ $cuotasEnMora }}</div>
            <div class="text-xs mt-1" style="color:#ef4444">$ {{ number_format($montoMoraUsd, 0, ',', '.') }} USD pendiente</div>
            <div class="stat-icon"><svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></div>
        </div>
    </div>

    {{-- ── Gráfico + Cuotas próximas ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Gráfico ventas por mes --}}
        <div class="erp-card lg:col-span-2">
            <div class="erp-card-header">
                <h2>Ventas completadas — Últimos 12 meses</h2>
                <a href="{{ route('reportes.index') }}" class="btn btn-ghost text-xs">Ver reportes →</a>
            </div>
            <div class="erp-card-body">
                <canvas id="chartVentas" height="120"></canvas>
            </div>
        </div>

        {{-- Cuotas próximas 7 días --}}
        <div class="erp-card">
            <div class="erp-card-header">
                <h2>Próximos vencimientos (7d)</h2>
            </div>
            <div class="overflow-y-auto" style="max-height:260px">
                @forelse($cuotasProximas as $c)
                    <div class="px-4 py-2.5 border-b flex flex-col gap-0.5" style="border-color:var(--border)">
                        <span class="text-xs font-semibold" style="color:var(--text)">{{ $c->cliente_nombre }}</span>
                        <span class="text-xs" style="color:var(--text-muted)">{{ $c->numero_venta }} — Cuota #{{ $c->numero_cuota }}</span>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold" style="color:var(--accent)">$ {{ number_format($c->total_cuota, 2, ',', '.') }}</span>
                            <span class="text-xs px-1.5 py-0.5 rounded"
                                style="background:{{ $c->fecha_vencimiento === now()->toDateString() ? '#ef444420' : '#6c63ff15' }};color:{{ $c->fecha_vencimiento === now()->toDateString() ? '#ef4444' : '#6c63ff' }}">
                                {{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm" style="color:var(--text-muted)">Sin vencimientos próximos</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── Ventas recientes + Últimos vehículos ─────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div class="erp-card">
            <div class="erp-card-header">
                <h2>Ventas recientes</h2>
                <a href="{{ route('ventas.index') }}" class="btn btn-ghost text-xs">Ver todas →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>N° Venta</th>
                            <th>Cliente</th>
                            <th>Vehículo</th>
                            <th>USD</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventasRecientes as $v)
                            <tr>
                                <td><a href="{{ route('ventas.show', $v->id) }}" style="color:var(--primary);text-decoration:none;font-size:.78rem;font-weight:600">{{ $v->numero_venta }}</a></td>
                                <td style="font-size:.8rem">{{ \Illuminate\Support\Str::limit($v->cliente_nombre, 18) }}</td>
                                <td style="font-size:.8rem">{{ $v->marca }} {{ $v->modelo }}</td>
                                <td style="font-size:.82rem;font-weight:600">$ {{ number_format($v->precio_venta_usd, 0, ',', '.') }}</td>
                                <td>
                                    @php $cls = match($v->estado) { 'COMPLETADO'=>'badge-disponible','EN_PROCESO','RESERVADO'=>'badge-preparacion','CANCELADO'=>'badge-vendido', default=>'badge-toma' }; @endphp
                                    <span class="badge-status {{ $cls }}" style="font-size:.65rem">{{ $v->estado }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem">Sin ventas aún</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="erp-card">
            <div class="erp-card-header">
                <h2>Últimos vehículos cargados</h2>
                <a href="{{ route('vehicles.index') }}" class="btn btn-ghost text-xs">Ver todos →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Chasis</th>
                            <th>Marca/Modelo</th>
                            <th>Estado</th>
                            <th>Costo USD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiculos as $v)
                            <tr>
                                <td><code style="color:var(--accent);font-size:.75rem">{{ $v->numero_chasis }}</code></td>
                                <td style="font-size:.82rem"><strong>{{ $v->marca }}</strong> {{ $v->modelo }}</td>
                                <td>
                                    @php $cls = match($v->estado) { 'DISPONIBLE'=>'badge-disponible','EN_PREPARACION'=>'badge-preparacion','TOMA'=>'badge-toma', default=>'badge-vendido' }; @endphp
                                    <span class="badge-status {{ $cls }}" style="font-size:.65rem">{{ $v->estado }}</span>
                                </td>
                                <td style="font-size:.82rem">$ {{ number_format($v->costo_origen_usd, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const raw = @json($ventasPorMes);
    const labels = raw.map(function(m) {
        const [y, mo] = m.mes.split('-');
        return new Date(y, mo - 1).toLocaleDateString('es-PY', { month: 'short', year: '2-digit' });
    });
    const cantidades = raw.map(function(m) { return m.cantidad; });
    const totales    = raw.map(function(m) { return parseFloat(m.total_usd) || 0; });

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const ctx = document.getElementById('chartVentas');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Monto USD',
                    data: totales,
                    backgroundColor: 'rgba(108,99,255,0.7)',
                    borderColor: '#6c63ff',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y1',
                },
                {
                    label: 'Cantidad',
                    data: cantidades,
                    type: 'line',
                    borderColor: '#00d4aa',
                    backgroundColor: 'rgba(0,212,170,0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    tension: 0.3,
                    fill: false,
                    yAxisID: 'y2',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: textColor, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.datasetIndex === 0) return ' USD ' + ctx.parsed.y.toLocaleString('de-DE', { minimumFractionDigits: 0 });
                            return ' ' + ctx.parsed.y + ' ventas';
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { color: textColor, font: { size: 10 } }, grid: { color: gridColor } },
                y1: { position: 'left', ticks: { color: textColor, font: { size: 10 }, callback: v => '$ ' + (v/1000).toFixed(0) + 'k' }, grid: { color: gridColor } },
                y2: { position: 'right', ticks: { color: textColor, font: { size: 10 } }, grid: { drawOnChartArea: false } },
            }
        }
    });
})();
</script>
@endpush
