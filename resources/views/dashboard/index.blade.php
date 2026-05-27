@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .kpi-card {
        border-radius: 16px;
        border: 1px solid var(--border);
        background: var(--surface);
        padding: 1.25rem 1.5rem;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
    .kpi-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .kpi-trend-up   { color: #22c55e; }
    .kpi-trend-down { color: #ef4444; }
    .kpi-trend-neutral { color: var(--text-muted); }
    .chart-card {
        border-radius: 16px;
        border: 1px solid var(--border);
        background: var(--surface);
        overflow: hidden;
    }
    .chart-header {
        padding: .875rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
    }
    .chart-header h3 { font-size: .8rem; font-weight: 600; color: var(--text); }
    .chart-body { padding: 1.25rem 1.5rem; }
    .activity-row {
        display: flex; align-items: center; gap: .875rem;
        padding: .75rem 1.25rem;
        border-bottom: 1px solid var(--border);
        transition: background .15s;
        cursor: pointer;
        text-decoration: none;
    }
    .activity-row:last-child { border-bottom: none; }
    .activity-row:hover { background: var(--surface2); }
    .cuota-date-badge {
        font-size: .65rem; font-weight: 700;
        padding: .2rem .55rem; border-radius: 6px;
        white-space: nowrap; flex-shrink: 0;
    }
    .cuota-hoy    { background: rgba(239,68,68,.12);  color: #ef4444; }
    .cuota-manana { background: rgba(245,158,11,.12); color: #f59e0b; }
    .cuota-prox   { background: rgba(108,99,255,.12); color: var(--primary); }
    .alert-banner {
        display: flex; align-items: center; gap: .75rem;
        padding: .75rem 1.125rem; border-radius: 12px;
        border: 1px solid; font-size: .82rem; font-weight: 500; margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')

@php
    $hoy = now()->toDateString();
    $manana = now()->addDay()->toDateString();
    $ingDiff = $ingresosUsdMesAnterior > 0
        ? round((($ingresosUsdMes - $ingresosUsdMesAnterior) / $ingresosUsdMesAnterior) * 100, 1)
        : null;
    $ventDiff = $ventasMesAnterior > 0
        ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
        : null;
    $margenPct = $ingresosUsdMes > 0 ? round(($margenUsdMes / $ingresosUsdMes) * 100, 1) : 0;
@endphp

{{-- ── Alertas críticas ───────────────────────────────────────────── --}}
@if($cuotasHoy > 0 || $stockBajoMinimo > 0)
<div class="flex flex-col sm:flex-row gap-2 mb-5">
    @if($cuotasHoy > 0)
    <div class="alert-banner flex-1" style="background:rgba(245,158,11,.07);border-color:rgba(245,158,11,.3);color:#f59e0b;">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
        </svg>
        <span><strong>{{ $cuotasHoy }} cuota{{ $cuotasHoy > 1 ? 's' : '' }}</strong> {{ $cuotasHoy > 1 ? 'vencen' : 'vence' }} hoy — verificar cobros del día.</span>
    </div>
    @endif
    @if($stockBajoMinimo > 0)
    <div id="alert-stock-bajo" class="alert-banner flex-1 flex items-center gap-2" style="background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.3);color:#ef4444;">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        <span class="flex-1"><strong>{{ $stockBajoMinimo }} producto{{ $stockBajoMinimo > 1 ? 's' : '' }}</strong> con stock bajo el mínimo.</span>
        <a href="{{ route('repuestos.index') }}?stock_bajo=1" class="text-xs underline hover:no-underline opacity-80 hover:opacity-100">Gestionar</a>
        <button type="button"
                onclick="dismissStockAlert()"
                title="Ocultar por 24 horas"
                class="p-1 rounded hover:bg-red-500/20 transition-colors flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <script>
        (function () {
            const DISMISS_KEY = 'dashboard.stock_bajo.dismissed_until';
            const dismissedUntil = parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10);
            if (dismissedUntil && Date.now() < dismissedUntil) {
                const el = document.getElementById('alert-stock-bajo');
                if (el) el.style.display = 'none';
            }
        })();
        function dismissStockAlert() {
            // Ocultar por 24 horas
            const until = Date.now() + 24 * 60 * 60 * 1000;
            localStorage.setItem('dashboard.stock_bajo.dismissed_until', String(until));
            const el = document.getElementById('alert-stock-bajo');
            if (el) el.style.display = 'none';
        }
    </script>
    @endif
</div>
@endif

{{-- ══ KPI CARDS ═══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 xl:grid-cols-3 gap-3 mb-5">

    {{-- Ingresos del mes --}}
    <div class="kpi-card">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="kpi-icon" style="background:rgba(0,212,170,.12);">
                <svg class="w-5 h-5" style="color:var(--accent)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            @if($ingDiff !== null)
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $ingDiff >= 0 ? 'kpi-trend-up bg-green-500/10' : 'kpi-trend-down bg-red-500/10' }}">
                {{ $ingDiff >= 0 ? '↑' : '↓' }} {{ abs($ingDiff) }}%
            </span>
            @endif
        </div>
        <div class="text-xs font-medium mb-1" style="color:var(--text-muted)">Ingresos del mes</div>
        <div class="text-2xl font-bold tracking-tight mb-0.5" style="color:var(--text)">${{ number_format($ingresosUsdMes, 0, ',', '.') }}</div>
        <div class="text-xs" style="color:var(--text-muted)">{{ $ventasMes }} venta{{ $ventasMes != 1 ? 's' : '' }} completadas</div>
    </div>

    {{-- Margen bruto --}}
    <div class="kpi-card">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="kpi-icon" style="background:rgba(108,99,255,.12);">
                <svg class="w-5 h-5" style="color:var(--primary)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $margenPct >= 10 ? 'kpi-trend-up bg-green-500/10' : 'kpi-trend-neutral bg-surface2' }}">
                {{ $margenPct }}%
            </span>
        </div>
        <div class="text-xs font-medium mb-1" style="color:var(--text-muted)">Margen bruto del mes</div>
        <div class="text-2xl font-bold tracking-tight mb-0.5" style="color:var(--text)">${{ number_format($margenUsdMes, 0, ',', '.') }}</div>
        <div class="text-xs" style="color:var(--text-muted)">sobre ${{ number_format($ingresosUsdMes, 0, ',', '.') }} ingresos</div>
    </div>

    {{-- Ventas del mes --}}
    <div class="kpi-card">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="kpi-icon" style="background:rgba(59,130,246,.12);">
                <svg class="w-5 h-5" style="color:#3b82f6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
            </div>
            @if($ventDiff !== null)
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $ventDiff >= 0 ? 'kpi-trend-up bg-green-500/10' : 'kpi-trend-down bg-red-500/10' }}">
                {{ $ventDiff >= 0 ? '↑' : '↓' }} {{ abs($ventDiff) }}%
            </span>
            @endif
        </div>
        <div class="text-xs font-medium mb-1" style="color:var(--text-muted)">Operaciones del mes</div>
        <div class="text-2xl font-bold tracking-tight mb-0.5" style="color:var(--text)">{{ $ventasMes }}</div>
        <div class="text-xs" style="color:var(--text-muted)">{{ $ventasMesAnterior }} el mes anterior</div>
    </div>

    {{-- Vehículos en stock --}}
    <div class="kpi-card">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="kpi-icon" style="background:rgba(139,83,255,.12);">
                <svg class="w-5 h-5" style="color:#8b53ff" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-500/10 text-green-500">
                {{ $disponibles }} disp.
            </span>
        </div>
        <div class="text-xs font-medium mb-1" style="color:var(--text-muted)">Vehículos en stock</div>
        <div class="text-2xl font-bold tracking-tight mb-0.5" style="color:var(--text)">{{ $totalVehiculos }}</div>
        <div class="text-xs" style="color:var(--text-muted)">{{ $enPreparacion }} en preparación</div>
    </div>

    {{-- Cuotas en mora --}}
    <div class="kpi-card">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="kpi-icon" style="background:rgba(239,68,68,.12);">
                <svg class="w-5 h-5" style="color:#ef4444" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                </svg>
            </div>
            @if($cuotasEnMora > 0)
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-500/10 text-red-500">Crítico</span>
            @else
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-500/10 text-green-500">OK</span>
            @endif
        </div>
        <div class="text-xs font-medium mb-1" style="color:var(--text-muted)">Cuotas en mora</div>
        <div class="text-2xl font-bold tracking-tight mb-0.5 {{ $cuotasEnMora > 0 ? 'text-red-500' : '' }}" style="{{ $cuotasEnMora == 0 ? 'color:var(--text)' : '' }}">{{ $cuotasEnMora }}</div>
        <div class="text-xs" style="color:var(--text-muted)">${{ number_format($montoMoraUsd, 0, ',', '.') }} USD pendiente</div>
    </div>

    {{-- Cobros próximos 7d --}}
    <div class="kpi-card">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="kpi-icon" style="background:rgba(245,158,11,.12);">
                <svg class="w-5 h-5" style="color:#f59e0b" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                </svg>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:rgba(245,158,11,.1);color:#f59e0b">7 días</span>
        </div>
        <div class="text-xs font-medium mb-1" style="color:var(--text-muted)">Cobros próximos</div>
        <div class="text-2xl font-bold tracking-tight mb-0.5" style="color:var(--text)">${{ number_format($cobrosProximos7dTotal, 0, ',', '.') }}</div>
        <div class="text-xs" style="color:var(--text-muted)">{{ $cuotasProximas->count() }} cuota{{ $cuotasProximas->count() != 1 ? 's' : '' }} por vencer</div>
    </div>

</div>

{{-- ══ GRÁFICOS ══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-5">

    {{-- Gráfico de área — ingresos 12 meses --}}
    <div class="chart-card xl:col-span-2">
        <div class="chart-header">
            <div>
                <h3>Rendimiento de Ingresos</h3>
                <p class="text-xs mt-0.5" style="color:var(--text-muted)">Ingresos y margen bruto — últimos 12 meses</p>
            </div>
            <div class="flex items-center gap-3 text-xs" style="color:var(--text-muted)">
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:var(--primary)"></span>Ingresos</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:var(--accent)"></span>Margen</span>
            </div>
        </div>
        <div class="chart-body" style="padding-bottom:.75rem">
            <div id="chartRevSkeleton" class="space-y-3">
                <div class="flex items-end gap-1.5 h-[220px]">
                    @foreach([45,65,40,80,55,70,60,90,50,85,45,75] as $h)
                        <div class="skeleton flex-1 rounded-t" style="height:{{ $h }}%"></div>
                    @endforeach
                </div>
                <div class="flex gap-1.5">@foreach(range(1,12) as $i)<div class="skeleton flex-1 h-2.5 rounded"></div>@endforeach</div>
            </div>
            <canvas id="chartRevenue" style="display:none;max-height:260px"></canvas>
        </div>
    </div>

    {{-- Donut — inventario por estado --}}
    <div class="chart-card flex flex-col">
        <div class="chart-header">
            <div>
                <h3>Inventario por Estado</h3>
                <p class="text-xs mt-0.5" style="color:var(--text-muted)">Distribución actual del stock</p>
            </div>
            <span class="text-lg font-bold" style="color:var(--text)">{{ $totalVehiculos }}</span>
        </div>

        @php
            $estadosMeta = [
                'DISPONIBLE'    => ['color'=>'#22c55e', 'label'=>'Disponible',      'bg'=>'rgba(34,197,94,.1)'],
                'EN_PREPARACION'=> ['color'=>'#f59e0b', 'label'=>'En preparación',  'bg'=>'rgba(245,158,11,.1)'],
                'TOMA'          => ['color'=>'#94a3b8', 'label'=>'Toma',            'bg'=>'rgba(148,163,184,.1)'],
                'VENDIDO'       => ['color'=>'#ef4444', 'label'=>'Vendido',         'bg'=>'rgba(239,68,68,.1)'],
            ];
        @endphp

        {{-- Donut centrado --}}
        <div class="flex items-center justify-center py-5" style="border-bottom:1px solid var(--border)">
            <div style="position:relative;width:148px;height:148px;flex-shrink:0;">
                <canvas id="chartDonut" width="148" height="148"></canvas>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;gap:1px">
                    <span class="text-2xl font-bold leading-none" style="color:var(--text)">{{ $totalVehiculos }}</span>
                    <span class="text-[0.65rem] font-medium uppercase tracking-wider" style="color:var(--text-muted)">unidades</span>
                </div>
            </div>
        </div>

        {{-- Leyenda 2×2 --}}
        <div class="grid grid-cols-2 gap-px flex-1" style="background:var(--border)">
            @foreach($estadosMeta as $key => $meta)
                @php
                    $val = $vehiculosPorEstado[$key] ?? 0;
                    $pct = $totalVehiculos > 0 ? round($val / $totalVehiculos * 100) : 0;
                @endphp
                <div class="flex flex-col gap-1 p-4" style="background:var(--surface)">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $meta['color'] }}"></span>
                        <span class="text-xs font-medium" style="color:var(--text-muted)">{{ $meta['label'] }}</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl font-bold leading-none" style="color:{{ $val > 0 ? $meta['color'] : 'var(--text-muted)' }}">{{ $val }}</span>
                        @if($totalVehiculos > 0)
                        <span class="text-xs font-medium" style="color:var(--text-muted)">{{ $pct }}%</span>
                        @endif
                    </div>
                    {{-- Barra de progreso --}}
                    <div class="h-1 rounded-full mt-0.5 overflow-hidden" style="background:var(--surface2)">
                        <div class="h-full rounded-full transition-all" style="width:{{ $pct }}%;background:{{ $meta['color'] }};opacity:.7;min-width:{{ $val > 0 ? '4px' : '0' }}"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ══ ACTIVIDAD ════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">

    {{-- Ventas recientes --}}
    <div class="chart-card">
        <div class="chart-header">
            <h3>Ventas recientes</h3>
            <a href="{{ route('ventas.index') }}" class="text-xs font-medium no-underline hover:underline" style="color:var(--primary)">Ver todas →</a>
        </div>
        @forelse($ventasRecientes as $v)
        @php
            $cls = match($v->estado) {
                'COMPLETADO' => 'badge-disponible',
                'CANCELADO'  => 'badge-vendido',
                'RESERVADO'  => 'badge-preparacion',
                default      => 'badge-toma'
            };
        @endphp
        <a href="{{ route('ventas.show', $v->id) }}" class="activity-row">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(108,99,255,.1)">
                <svg class="w-4 h-4" style="color:var(--primary)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-foreground truncate">{{ \Illuminate\Support\Str::limit($v->cliente_nombre, 22) }}</span>
                    <span class="badge-status {{ $cls }} flex-shrink-0" style="font-size:.65rem;padding:.15rem .5rem">{{ $v->estado }}</span>
                </div>
                <div class="text-xs text-muted-foreground mt-0.5">
                    {{ $v->marca }} {{ $v->modelo }} &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}
                </div>
            </div>
            <div class="text-right flex-shrink-0">
                <div class="text-sm font-bold" style="color:var(--accent)">${{ number_format($v->precio_venta_usd, 0, ',', '.') }}</div>
                <div class="text-xs" style="color:var(--text-muted)">{{ $v->numero_venta }}</div>
            </div>
        </a>
        @empty
        <div class="erp-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            <p>No hay ventas registradas aún</p>
            @can('ventas.crear')<a href="{{ route('ventas.create') }}" class="btn btn-primary text-xs mt-1">+ Nueva Venta</a>@endcan
        </div>
        @endforelse
    </div>

    {{-- Vencimientos próximos --}}
    <div class="chart-card">
        <div class="chart-header">
            <h3>Vencimientos próximos</h3>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background:rgba(245,158,11,.1);color:#f59e0b">7 días</span>
        </div>
        @forelse($cuotasProximas as $c)
        @php
            $esFechaHoy    = $c->fecha_vencimiento === $hoy;
            $esFechaManana = $c->fecha_vencimiento === $manana;
            $badgeClass    = $esFechaHoy ? 'cuota-hoy' : ($esFechaManana ? 'cuota-manana' : 'cuota-prox');
            $fechaLabel    = $esFechaHoy ? 'HOY' : ($esFechaManana ? 'MAÑANA' : \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m'));
        @endphp
        <div class="activity-row">
            <span class="cuota-date-badge {{ $badgeClass }}">{{ $fechaLabel }}</span>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-foreground truncate">{{ \Illuminate\Support\Str::limit($c->cliente_nombre, 24) }}</div>
                <div class="text-xs text-muted-foreground mt-0.5">{{ $c->numero_venta }} &nbsp;·&nbsp; Cuota #{{ $c->numero_cuota }}</div>
            </div>
            <div class="text-sm font-bold flex-shrink-0" style="color:#00d4aa">${{ number_format($c->total_cuota, 0, ',', '.') }}</div>
        </div>
        @empty
        <div class="erp-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            <p>Sin vencimientos en los próximos 7 días</p>
        </div>
        @endforelse
    </div>

</div>

{{-- ══ REPUESTOS BAJO STOCK ══════════════════════════════════════════════ --}}
@if($repuestosBajos->count() > 0)
<div class="chart-card">
    <div class="chart-header">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" style="color:#ef4444" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <h3>Productos con stock bajo mínimo</h3>
        </div>
        <a href="{{ route('repuestos.index') }}" class="text-xs font-medium no-underline hover:underline" style="color:#ef4444">Ver todos →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="text-center">Stock actual</th>
                    <th class="text-center">Mínimo</th>
                    <th class="text-center">Déficit</th>
                    @can('repuestos.editar')<th class="text-center">Acción</th>@endcan
                </tr>
            </thead>
            <tbody>
                @foreach($repuestosBajos as $r)
                    @php
                        $stockActual = (int) $r->stock_actual;
                        $stockMinimo = (int) $r->stock_minimo;
                        $deficit     = max(0, $stockMinimo - $stockActual);
                    @endphp
                <tr>
                    <td><code class="text-xs font-mono" style="color:#00d4aa">{{ $r->codigo }}</code></td>
                    <td class="text-sm text-foreground">{{ \Illuminate\Support\Str::limit($r->descripcion, 48) }}</td>
                    <td class="text-center">
                        <span class="font-bold text-sm text-red-500">{{ number_format($stockActual, 0, ',', '.') }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-sm text-muted-foreground">{{ number_format($stockMinimo, 0, ',', '.') }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge-status badge-vendido" title="Faltan {{ $deficit }} {{ $deficit === 1 ? 'unidad' : 'unidades' }} para llegar al stock mínimo">
                            {{ $deficit }}
                        </span>
                    </td>
                    @can('repuestos.editar')
                    <td class="text-center">
                        <form method="POST"
                              action="{{ route('repuestos.toggleActive', $r->id) }}"
                              class="inline"
                              onsubmit="return confirm('¿Descontinuar {{ addslashes($r->codigo) }}? Ya no aparecerá en alertas de stock ni en ventas. Es reversible.')">
                            @csrf
                            <input type="hidden" name="discontinuar" value="1">
                            <button class="px-2 py-1 rounded-md text-[0.65rem] font-bold uppercase tracking-wider hover:bg-amber-500/20 text-amber-500 transition-all"
                                    title="No vender más este producto">
                                Descontinuar
                            </button>
                        </form>
                    </td>
                    @endcan
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const isDark    = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
    const tickColor = isDark ? '#94a3b8' : '#64748b';
    const primaryColor = isDark ? '#bb86fc' : '#6c63ff';
    const accentColor  = isDark ? '#03dac6' : '#059669'; // Mas oscuro para Light Mode
    const surfaceColor = isDark ? '#1e1e1e' : '#ffffff';
    const borderColor  = isDark ? '#2d2d2d' : '#cbd5e1';

    /* ── Helpers ────────────────────────────────────────────────────── */
    function fmtUsd(v) {
        if (v >= 1000000) return '$ ' + (v / 1000000).toFixed(1) + 'M';
        if (v >= 1000)    return '$ ' + (v / 1000).toFixed(0) + 'k';
        return '$ ' + v;
    }

    /* ── Gradient factory ───────────────────────────────────────────── */
    function makeGradient(ctx, color, alpha1, alpha2) {
        const g = ctx.createLinearGradient(0, 0, 0, 300);
        g.addColorStop(0,   color.replace('1)', alpha1 + ')'));
        g.addColorStop(1,   color.replace('1)', alpha2 + ')'));
        return g;
    }

    /* ══ Chart 1: Revenue Area ═════════════════════════════════════════ */
    (function buildRevenue() {
        const raw    = @json($ventasPorMes);
        const labels = raw.map(function (m) {
            const [y, mo] = m.mes.split('-');
            return new Date(y, mo - 1).toLocaleDateString('es-PY', { month: 'short', year: '2-digit' });
        });
        const totales  = raw.map(function (m) { return parseFloat(m.total_usd)  || 0; });
        const margenes = raw.map(function (m) { return parseFloat(m.margen_usd) || 0; });

        const canvas = document.getElementById('chartRevenue');
        if (!canvas) return;

        document.getElementById('chartRevSkeleton').style.display = 'none';
        canvas.style.display = 'block';

        const ctx = canvas.getContext('2d');
        const gradPrimary = makeGradient(ctx, isDark ? 'rgba(187,134,252,1)' : 'rgba(108,99,255,1)', '0.35', '0.02');
        const gradAccent  = makeGradient(ctx, isDark ? 'rgba(3,218,198,1)' : 'rgba(0,212,170,1)',  '0.25', '0.02');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Ingresos USD',
                        data: totales,
                        borderColor: primaryColor,
                        backgroundColor: gradPrimary,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: primaryColor,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Margen bruto',
                        data: margenes,
                        borderColor: accentColor,
                        backgroundColor: gradAccent,
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        pointBackgroundColor: accentColor,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y',
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: surfaceColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        titleColor: tickColor,
                        bodyColor: tickColor,
                        padding: 12,
                        callbacks: {
                            label: function (c) {
                                return '  ' + c.dataset.label + ': ' + fmtUsd(c.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor, drawTicks: false },
                        border: { display: false },
                        ticks: { color: tickColor, font: { size: 11 }, padding: 6 }
                    },
                    y: {
                        position: 'left',
                        grid: { color: gridColor, drawTicks: false },
                        border: { display: false, dash: [4, 4] },
                        ticks: { color: tickColor, font: { size: 11 }, padding: 8, callback: fmtUsd }
                    }
                }
            }
        });
    })();

    /* ══ Chart 2: Donut inventario ══════════════════════════════════════ */
    (function buildDonut() {
        const canvas = document.getElementById('chartDonut');
        if (!canvas) return;

        const data   = @json($vehiculosPorEstado);
        const order  = ['DISPONIBLE', 'EN_PREPARACION', 'TOMA', 'VENDIDO'];
        const colors = { DISPONIBLE: '#22c55e', EN_PREPARACION: '#f59e0b', TOMA: '#94a3b8', VENDIDO: '#ef4444' };
        const labels = { DISPONIBLE: 'Disponible', EN_PREPARACION: 'En preparación', TOMA: 'Toma', VENDIDO: 'Vendido' };

        const vals  = order.map(function (k) { return data[k] || 0; });
        const cols  = order.map(function (k) { return colors[k]; });
        const labs  = order.map(function (k) { return labels[k]; });
        const total = vals.reduce(function (a, b) { return a + b; }, 0);

        // Si no hay datos muestra un anillo vacío grisáceo
        const surfaceColor = isDark ? '#2d3148' : '#e5e7eb';

        new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: total > 0 ? labs : ['Sin datos'],
                datasets: [{
                    data:            total > 0 ? vals : [1],
                    backgroundColor: total > 0 ? cols : [surfaceColor],
                    borderWidth:     total > 0 ? 3 : 0,
                    borderColor:     surfaceColor,
                    hoverBorderWidth: 4,
                    hoverOffset:     6,
                }]
            },
            options: {
                responsive: false,
                cutout: '74%',
                animation: { animateRotate: true, duration: 700 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: total > 0,
                        backgroundColor: surfaceColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        titleColor: tickColor,
                        bodyColor: tickColor,
                        padding: 10,
                        callbacks: {
                            label: function (c) {
                                const pct = total > 0 ? Math.round(c.parsed / total * 100) : 0;
                                return '  ' + c.label + ': ' + c.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    })();

    /* ── Re-render on theme change ─────────────────────────────────── */
    document.getElementById('themeToggle')?.addEventListener('click', function () {
        setTimeout(function () { location.reload(); }, 350);
    });
})();
</script>
@endpush
