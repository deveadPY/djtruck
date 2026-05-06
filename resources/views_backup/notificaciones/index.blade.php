@extends('layouts.app')
@section('title', 'Centro de Notificaciones')
@section('page-title', '🔔 Alertas de Cuotas')

@push('styles')
<style>
    .notif-tabs { display:flex; gap:.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
    .notif-tab-btn {
        padding:.45rem 1.1rem; border-radius:8px; font-size:.78rem; font-weight:600;
        border:1px solid var(--border); background:var(--surface2); color:var(--text-muted);
        cursor:pointer; transition:.2s; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem;
    }
    .notif-tab-btn.active { color:#fff; border-color:transparent; }
    .notif-tab-btn.danger.active  { background:var(--danger); }
    .notif-tab-btn.warning.active { background:var(--warning); color:#000; }
    .notif-tab-btn.orange.active  { background:#f97316; }
    .notif-panel { display:none; }
    .notif-panel.active { display:block; }
    .dias-mora { display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.65rem; font-weight:700; background:rgba(239,68,68,.15); color:var(--danger); }
    .stat-row { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
    .stat-mini {
        flex:1; min-width:140px; background:var(--surface2); border:1px solid var(--border);
        border-radius:10px; padding:.85rem 1.1rem;
    }
    .stat-mini .sm-label { font-size:.68rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
    .stat-mini .sm-value { font-size:1.4rem; font-weight:700; margin-top:.25rem; }
</style>
@endpush

@section('content')

{{-- ── Stats resumen ──────────────────────────────────────────────────── --}}
<div class="stat-row">
    <div class="stat-mini">
        <div class="sm-label">⚠ Vencidas</div>
        <div class="sm-value" style="color:var(--danger)">{{ $vencidas->total() }}</div>
    </div>
    <div class="stat-mini">
        <div class="sm-label">🔴 En Mora</div>
        <div class="sm-value" style="color:#f97316">{{ $enMora->total() }}</div>
    </div>
    <div class="stat-mini">
        <div class="sm-label">📅 Próximas 7 días</div>
        <div class="sm-value" style="color:var(--warning)">{{ $proximas->total() }}</div>
    </div>
    <div class="stat-mini">
        <div class="sm-label">🚨 Total críticas</div>
        <div class="sm-value" style="color:var(--danger)">{{ $vencidas->total() + $enMora->total() }}</div>
    </div>
</div>

{{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
<div class="notif-tabs">
    <button class="notif-tab-btn danger active"  onclick="showTab('vencidas',this)">
        ⚠ Vencidas <span style="background:rgba(255,255,255,.25);border-radius:999px;padding:.05rem .4rem;">{{ $vencidas->total() }}</span>
    </button>
    <button class="notif-tab-btn orange" onclick="showTab('mora',this)">
        🔴 En Mora <span style="background:rgba(255,255,255,.25);border-radius:999px;padding:.05rem .4rem;">{{ $enMora->total() }}</span>
    </button>
    <button class="notif-tab-btn warning" onclick="showTab('proximas',this)">
        📅 Próximas 7 días <span style="background:rgba(0,0,0,.15);border-radius:999px;padding:.05rem .4rem;">{{ $proximas->total() }}</span>
    </button>
</div>

{{-- ── Tab: Vencidas ───────────────────────────────────────────────────── --}}
<div id="panel-vencidas" class="notif-panel active">
    <div class="card">
        <div class="card-header">
            <h2 style="color:var(--danger)">⚠ Cuotas Vencidas</h2>
            <span style="font-size:.75rem;color:var(--text-muted)">Cuotas PENDIENTE con fecha de vencimiento pasada</span>
        </div>
        @if($vencidas->isEmpty())
            <div style="padding:2rem;text-align:center;color:var(--text-muted)">✔ No hay cuotas vencidas</div>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>N° Venta</th>
                    <th>Cuota</th>
                    <th>Vencimiento</th>
                    <th>Días mora</th>
                    <th>Monto</th>
                    <th>Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($vencidas as $row)
                @php
                    $dias = \Carbon\Carbon::parse($row->fecha_vencimiento)->diffInDays(\Carbon\Carbon::today());
                    $monto = (float)$row->capital + (float)$row->interes;
                @endphp
                <tr>
                    <td style="font-weight:600">{{ $row->cliente_nombre }}</td>
                    <td>{{ $row->numero_venta }}</td>
                    <td>{{ $row->numero_cuota }}/{{ $row->total_cuotas }}</td>
                    <td style="color:var(--danger);font-weight:600">{{ \Carbon\Carbon::parse($row->fecha_vencimiento)->format('d/m/Y') }}</td>
                    <td><span class="dias-mora">{{ $dias }}d</span></td>
                    <td style="font-weight:600">{{ $row->moneda }} {{ number_format($monto, 2, ',', '.') }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ $row->cliente_email ?: '—' }}</td>
                    <td>
                        <a href="{{ route('planes_cuotas.show', $row->plan_id) }}" class="btn btn-ghost" style="font-size:.72rem;padding:.3rem .7rem">
                            Ver Plan
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div style="padding:.75rem 1rem">{{ $vencidas->appends(request()->except('venc_page'))->links() }}</div>
        @endif
    </div>
</div>

{{-- ── Tab: En Mora ────────────────────────────────────────────────────── --}}
<div id="panel-mora" class="notif-panel">
    <div class="card">
        <div class="card-header">
            <h2 style="color:#f97316">🔴 Cuotas en Mora</h2>
            <span style="font-size:.75rem;color:var(--text-muted)">Cuotas con estado EN_MORA</span>
        </div>
        @if($enMora->isEmpty())
            <div style="padding:2rem;text-align:center;color:var(--text-muted)">✔ No hay cuotas en mora</div>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>N° Venta</th>
                    <th>Cuota</th>
                    <th>Vencimiento</th>
                    <th>Días mora</th>
                    <th>Monto + Mora</th>
                    <th>Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($enMora as $row)
                @php
                    $dias = \Carbon\Carbon::parse($row->fecha_vencimiento)->diffInDays(\Carbon\Carbon::today());
                    $monto = (float)$row->capital + (float)$row->interes + (float)$row->interes_mora;
                @endphp
                <tr>
                    <td style="font-weight:600">{{ $row->cliente_nombre }}</td>
                    <td>{{ $row->numero_venta }}</td>
                    <td>{{ $row->numero_cuota }}/{{ $row->total_cuotas }}</td>
                    <td style="color:#f97316;font-weight:600">{{ \Carbon\Carbon::parse($row->fecha_vencimiento)->format('d/m/Y') }}</td>
                    <td><span class="dias-mora" style="background:rgba(249,115,22,.15);color:#f97316">{{ $dias }}d</span></td>
                    <td style="font-weight:600;color:#f97316">{{ $row->moneda }} {{ number_format($monto, 2, ',', '.') }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ $row->cliente_email ?: '—' }}</td>
                    <td>
                        <a href="{{ route('planes_cuotas.show', $row->plan_id) }}" class="btn btn-ghost" style="font-size:.72rem;padding:.3rem .7rem">
                            Ver Plan
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div style="padding:.75rem 1rem">{{ $enMora->appends(request()->except('mora_page'))->links() }}</div>
        @endif
    </div>
</div>

{{-- ── Tab: Próximas ───────────────────────────────────────────────────── --}}
<div id="panel-proximas" class="notif-panel">
    <div class="card">
        <div class="card-header">
            <h2 style="color:var(--warning)">📅 Cuotas Próximas a Vencer</h2>
            <span style="font-size:.75rem;color:var(--text-muted)">Cuotas PENDIENTE que vencen en los próximos 7 días</span>
        </div>
        @if($proximas->isEmpty())
            <div style="padding:2rem;text-align:center;color:var(--text-muted)">✔ No hay cuotas próximas esta semana</div>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>N° Venta</th>
                    <th>Cuota</th>
                    <th>Vencimiento</th>
                    <th>Días restantes</th>
                    <th>Monto</th>
                    <th>Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($proximas as $row)
                @php
                    $dias = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($row->fecha_vencimiento));
                    $monto = (float)$row->capital + (float)$row->interes;
                @endphp
                <tr>
                    <td style="font-weight:600">{{ $row->cliente_nombre }}</td>
                    <td>{{ $row->numero_venta }}</td>
                    <td>{{ $row->numero_cuota }}/{{ $row->total_cuotas }}</td>
                    <td style="color:var(--warning);font-weight:600">{{ \Carbon\Carbon::parse($row->fecha_vencimiento)->format('d/m/Y') }}</td>
                    <td>
                        <span style="display:inline-block;padding:.15rem .5rem;border-radius:999px;font-size:.65rem;font-weight:700;background:rgba(245,158,11,.15);color:var(--warning)">
                            {{ $dias === 0 ? 'Hoy' : $dias . 'd' }}
                        </span>
                    </td>
                    <td style="font-weight:600">{{ $row->moneda }} {{ number_format($monto, 2, ',', '.') }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ $row->cliente_email ?: '—' }}</td>
                    <td>
                        <a href="{{ route('planes_cuotas.show', $row->plan_id) }}" class="btn btn-ghost" style="font-size:.72rem;padding:.3rem .7rem">
                            Ver Plan
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div style="padding:.75rem 1rem">{{ $proximas->appends(request()->except('prox_page'))->links() }}</div>
        @endif
    </div>
</div>

<script>
function showTab(name, btn) {
    document.querySelectorAll('.notif-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.notif-tab-btn').forEach(function(b){
        b.classList.remove('active');
    });
    document.getElementById('panel-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>

@endsection
