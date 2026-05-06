@extends('layouts.app')
@section('title', 'Finanzas')
@section('page-title', 'Panel Financiero')

@push('styles')
<style>
    .caja-card {
        border-radius: 1rem;
        border: 1px solid var(--border);
        background: var(--surface);
        overflow: hidden;
    }
    .caja-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .caja-card-title {
        display: flex;
        align-items: center;
        gap: .65rem;
        font-size: .9rem;
        font-weight: 700;
        color: var(--text);
    }
    .caja-badge {
        font-size: .68rem;
        font-weight: 600;
        padding: .2rem .6rem;
        border-radius: 99px;
        letter-spacing: .04em;
    }
    .badge-chica   { background: rgba(245,158,11,.12); color: #f59e0b; }
    .badge-capital { background: rgba(108,99,255,.12);  color: #6c63ff; }
    .saldo-usd {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -.02em;
        line-height: 1;
    }
    .saldo-positivo { color: #10b981; }
    .saldo-negativo { color: #ef4444; }
    .kpi-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1px;
        background: var(--border);
    }
    .kpi-cell {
        background: var(--surface2);
        padding: .85rem 1.25rem;
    }
    .kpi-label { font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin-bottom: .2rem; }
    .kpi-val   { font-size: 1rem; font-weight: 700; }
    .mov-row   { display: flex; justify-content: space-between; align-items: center; padding: .6rem 1.5rem; font-size: .8rem; border-bottom: 1px solid var(--border); }
    .mov-row:last-child { border-bottom: none; }
    .mov-ingreso { color: #10b981; font-weight: 700; }
    .mov-egreso  { color: #ef4444; font-weight: 700; }

    /* ── Modal movimiento ── */
    .mov-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,.55);
        backdrop-filter: blur(3px); z-index: 200;
        display: flex; align-items: center; justify-content: center; padding: 1rem;
    }
    .mov-box {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 1rem; width: 100%; max-width: 460px;
        box-shadow: 0 24px 64px rgba(0,0,0,.4); overflow: hidden;
    }
    .mov-header {
        padding: 1.2rem 1.5rem; color: #fff;
        display: flex; align-items: center; justify-content: space-between;
    }
    .mov-header-ingreso { background: linear-gradient(135deg,#059669,#10b981); }
    .mov-header-egreso  { background: linear-gradient(135deg,#dc2626,#ef4444); }
    .mov-body { padding: 1.5rem; }
    .tipo-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .28rem .75rem; border-radius: 99px;
        font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    }
    .tipo-pill-ingreso { background: rgba(16,185,129,.15); color: #059669; }
    .tipo-pill-egreso  { background: rgba(239,68,68,.15);  color: #dc2626; }

    /* ── Transfer modal (legacy) ── */
    .transfer-modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 200;
        background: rgba(0,0,0,.55); backdrop-filter: blur(3px);
        align-items: center; justify-content: center; padding: 1rem;
    }
    .transfer-modal-overlay.open { display: flex; }
    .transfer-box {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 1rem; padding: 1.5rem; width: 100%; max-width: 440px;
        box-shadow: 0 20px 60px rgba(0,0,0,.4);
    }
</style>
@endpush

@section('content')

{{-- Alpine.js wrapper para el modal compartido de movimientos --}}
<div x-data="cajaDashModal()">

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    {{-- ── KPIs del mes ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-label">Ingresos del mes</div>
            <div class="stat-value text-green-500">$ {{ number_format($ingresosMes, 2, ',', '.') }}</div>
            <div class="text-xs mt-1" style="color:var(--text-muted)">USD acumulado</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Egresos del mes</div>
            <div class="stat-value text-red-500">$ {{ number_format($egresosMes, 2, ',', '.') }}</div>
            <div class="text-xs mt-1" style="color:var(--text-muted)">USD acumulado</div>
        </div>
        <div class="stat-card {{ $cuotasVencidas > 0 ? 'warning' : '' }}">
            <div class="stat-label">Cuotas vencidas</div>
            <div class="stat-value {{ $cuotasVencidas > 0 ? 'text-amber-500' : 'text-green-500' }}">
                {{ $cuotasVencidas }}
            </div>
            <div class="text-xs mt-1" style="color:var(--text-muted)">
                @if($cuotasVencidas > 0)
                    <a href="{{ route('planes_cuotas.index') }}" style="color:#f59e0b">Ver cuotas →</a>
                @else
                    Sin atrasos
                @endif
            </div>
        </div>
    </div>

    {{-- ── Dos cajas ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

        {{-- CAJA CHICA --}}
        <div class="caja-card">
            <div class="caja-card-header">
                <div class="caja-card-title">
                    <svg class="w-5 h-5" style="color:#f59e0b" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                    </svg>
                    Caja Chica
                    <span class="caja-badge badge-chica">Gastos locales</span>
                </div>
                {{-- Acciones rápidas --}}
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('finance.caja.show', $chica->codigo) }}" class="btn btn-primary text-xs py-1.5 px-4 shadow-sm">
                        Ver Movimientos →
                    </a>
                </div>
            </div>

            <div class="p-5">
                <div class="mb-4">
                    <div class="text-xs mb-1" style="color:var(--text-muted)">Saldo actual</div>
                    <div class="saldo-usd {{ $chica->saldo_usd >= 0 ? 'saldo-positivo' : 'saldo-negativo' }}">
                        $ {{ number_format($chica->saldo_usd, 2, ',', '.') }}
                        <span style="font-size:.9rem;font-weight:500">USD</span>
                    </div>
                </div>
                <div class="kpi-row rounded-xl overflow-hidden mb-4">
                    <div class="kpi-cell">
                        <div class="kpi-label">Ingresos este mes</div>
                        <div class="kpi-val text-green-500">$ {{ number_format($chica->ingresos_mes, 2, ',', '.') }}</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kpi-label">Egresos este mes</div>
                        <div class="kpi-val text-red-500">$ {{ number_format($chica->egresos_mes, 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-muted)">Últimos movimientos</div>
                @forelse($chica->recientes as $m)
                    <div class="mov-row">
                        <div>
                            <div class="font-medium" style="font-size:.8rem">{{ \Str::limit($m->concepto, 40) }}</div>
                            <div style="font-size:.72rem;color:var(--text-muted)">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m H:i') }}</div>
                        </div>
                        <div class="{{ $m->tipo === 'INGRESO' ? 'mov-ingreso' : 'mov-egreso' }}">
                            {{ $m->tipo === 'INGRESO' ? '+' : '-' }} $ {{ number_format($m->monto_usd, 2, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-sm" style="color:var(--text-muted)">Sin movimientos recientes</div>
                @endforelse
            </div>
        </div>

        {{-- CAJA CAPITAL --}}
        <div class="caja-card">
            <div class="caja-card-header">
                <div class="caja-card-title">
                    <svg class="w-5 h-5" style="color:#6c63ff" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/>
                    </svg>
                    Caja Capital
                    <span class="caja-badge badge-capital">Ventas &amp; cuotas</span>
                </div>
                {{-- Acciones rápidas --}}
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('finance.caja.show', $capital->codigo) }}" class="btn btn-primary text-xs py-1.5 px-4 shadow-sm">
                        Ver Movimientos →
                    </a>
                </div>
            </div>

            <div class="p-5">
                <div class="mb-4">
                    <div class="text-xs mb-1" style="color:var(--text-muted)">Saldo actual</div>
                    <div class="saldo-usd {{ $capital->saldo_usd >= 0 ? 'saldo-positivo' : 'saldo-negativo' }}">
                        $ {{ number_format($capital->saldo_usd, 2, ',', '.') }}
                        <span style="font-size:.9rem;font-weight:500">USD</span>
                    </div>
                </div>
                <div class="kpi-row rounded-xl overflow-hidden mb-4">
                    <div class="kpi-cell">
                        <div class="kpi-label">Ingresos este mes</div>
                        <div class="kpi-val text-green-500">$ {{ number_format($capital->ingresos_mes, 2, ',', '.') }}</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kpi-label">Egresos este mes</div>
                        <div class="kpi-val text-red-500">$ {{ number_format($capital->egresos_mes, 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-muted)">Últimos movimientos</div>
                @forelse($capital->recientes as $m)
                    <div class="mov-row">
                        <div>
                            <div class="font-medium" style="font-size:.8rem">{{ \Str::limit($m->concepto, 40) }}</div>
                            <div style="font-size:.72rem;color:var(--text-muted)">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m H:i') }}</div>
                        </div>
                        <div class="{{ $m->tipo === 'INGRESO' ? 'mov-ingreso' : 'mov-egreso' }}">
                            {{ $m->tipo === 'INGRESO' ? '+' : '-' }} $ {{ number_format($m->monto_usd, 2, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-sm" style="color:var(--text-muted)">Sin movimientos recientes</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Acción: transferir ───────────────────────────────────────────── --}}
    <div class="flex justify-end mb-6">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('transferModal').classList.add('open')">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
            </svg>
            Transferir entre cajas
        </button>
    </div>

    {{-- ── Mini gráfico flujo 7 días ────────────────────────────────────── --}}
    <div class="erp-card">
        <div class="erp-card-header">
            <h2>Flujo de caja — últimos 7 días (USD)</h2>
        </div>
        <div class="erp-card-body">
            <canvas id="flujoChart" style="max-height:200px"></canvas>
        </div>
    </div>

    {{-- ── Modal movimiento rápido (compartido, Alpine.js) ─────────────── --}}
    <div class="mov-overlay" x-show="show" x-transition.opacity @click.self="show=false" style="display:none">
        <div class="mov-box" @click.stop x-transition>

            <div class="mov-header" :class="tipo === 'INGRESO' ? 'mov-header-ingreso' : 'mov-header-egreso'">
                <div>
                    <div class="font-bold text-base"
                         x-text="tipo === 'INGRESO' ? '↑ Agregar efectivo' : '↓ Retirar efectivo'"></div>
                    <div class="text-xs opacity-75 mt-0.5" x-text="cajaNombre"></div>
                </div>
                <button type="button" @click="show=false" class="opacity-75 hover:opacity-100 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mov-body">
                <form method="POST" :action="formAction">
                    @csrf
                    <input type="hidden" name="tipo" :value="tipo">

                    <div class="mb-4">
                        <span class="tipo-pill" :class="tipo === 'INGRESO' ? 'tipo-pill-ingreso' : 'tipo-pill-egreso'">
                            <span x-text="tipo === 'INGRESO' ? '↑' : '↓'"></span>
                            <span x-text="tipo === 'INGRESO' ? 'Ingreso de efectivo' : 'Egreso de efectivo'"></span>
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group">
                                <label class="form-label">Moneda *</label>
                                <select name="moneda" class="form-input" required>
                                    <template x-for="m in ['USD','PYG','BRL']" :key="m">
                                        <option :value="m" :selected="m === monedaPrincipal" x-text="m"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Monto *</label>
                                <input type="number" name="monto" class="form-input"
                                       step="0.01" min="0.01" required placeholder="0.00"
                                       x-ref="montoInput">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Concepto / Descripción *</label>
                            <input type="text" name="concepto" class="form-input" required maxlength="300"
                                   :placeholder="tipo === 'INGRESO'
                                       ? 'Ej: Reposición caja chica, aporte...'
                                       : 'Ej: Gastos de oficina, suministros...'">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" class="btn btn-ghost" @click="show=false">Cancelar</button>
                        <button type="submit" class="btn"
                                :class="tipo === 'INGRESO' ? 'btn-success' : 'btn-danger'"
                                x-text="tipo === 'INGRESO' ? '↑ Registrar ingreso' : '↓ Registrar egreso'">
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>{{-- /Alpine wrapper --}}

{{-- ── Modal transferencia ──────────────────────────────────────────── --}}
<div class="transfer-modal-overlay" id="transferModal">
    <div class="transfer-box">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem;color:var(--text)">Transferir entre cajas</h3>
        <form method="POST" action="{{ route('finance.transferir') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Origen *</label>
                    <select name="origen" class="form-input" required>
                        <option value="CAJA_CHICA">Caja Chica</option>
                        <option value="CAJA_CAPITAL">Caja Capital</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Destino *</label>
                    <select name="destino" class="form-input" required>
                        <option value="CAJA_CAPITAL">Caja Capital</option>
                        <option value="CAJA_CHICA">Caja Chica</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Moneda *</label>
                    <select name="moneda" class="form-input" required>
                        @foreach(['USD','PYG','BRL'] as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Monto *</label>
                    <input type="number" name="monto" class="form-input" step="0.01" min="0.01" required>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Concepto (opcional)</label>
                    <input type="text" name="concepto" class="form-input" maxlength="255"
                        placeholder="Ej: Reposición de caja chica">
                </div>
            </div>
            <div class="flex gap-2 justify-end mt-4">
                <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('transferModal').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Transferir</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    // Alpine component
    document.addEventListener('alpine:init', () => {
        Alpine.data('cajaDashModal', () => ({
            show: false,
            tipo: 'INGRESO',
            cajaCodigo: '',
            cajaNombre: '',
            monedaPrincipal: 'USD',
            formAction: '',
            abrir(tipo, codigo, nombre, moneda, action) {
                this.tipo            = tipo;
                this.cajaCodigo      = codigo;
                this.cajaNombre      = nombre;
                this.monedaPrincipal = moneda;
                this.formAction      = action;
                this.show            = true;
                this.$nextTick(() => this.$refs.montoInput?.focus());
            },
        }));
    });

    // Cerrar modal transferencia al click fuera
    document.getElementById('transferModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });

    // Flujo 7 días
    const flujoData = @json($flujo7dias);
    const labels    = flujoData.map(d => {
        const [y, m, day] = d.fecha.split('-');
        return `${day}/${m}`;
    });

    new Chart(document.getElementById('flujoChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ingresos USD',
                    data: flujoData.map(d => parseFloat(d.ingresos) || 0),
                    backgroundColor: 'rgba(16,185,129,.7)',
                    borderRadius: 6,
                },
                {
                    label: 'Egresos USD',
                    data: flujoData.map(d => parseFloat(d.egresos) || 0),
                    backgroundColor: 'rgba(239,68,68,.7)',
                    borderRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: getComputedStyle(document.body).getPropertyValue('--text').trim() } } },
            scales: {
                x: { ticks: { color: '#6b7280' }, grid: { color: 'rgba(107,114,128,.1)' } },
                y: { ticks: { color: '#6b7280', callback: v => '$ ' + v.toLocaleString() }, grid: { color: 'rgba(107,114,128,.1)' } },
            },
        },
    });
</script>
@endpush
@endsection
