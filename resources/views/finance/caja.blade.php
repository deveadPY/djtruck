@extends('layouts.app')
@section('title', $caja->nombre)
@section('page-title', $caja->nombre)

@push('styles')
<style>
    .badge-ingreso { background:rgba(16,185,129,.12); color:#10b981; }
    .badge-egreso  { background:rgba(239,68,68,.12);  color:#ef4444; }
    .ref-chip {
        font-size:.68rem; padding:.15rem .5rem; border-radius:99px;
        background:var(--surface2); color:var(--text-muted);
        font-weight:500; white-space:nowrap;
    }
    /* Modal */
    .mov-modal-overlay {
        position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:50;
        display:flex; align-items:center; justify-content:center; padding:1rem;
    }
    .mov-modal-box {
        background:var(--surface); border-radius:.875rem; width:100%; max-width:460px;
        box-shadow:0 24px 64px rgba(0,0,0,.35); overflow:hidden;
    }
    .mov-modal-header {
        padding:1.25rem 1.5rem; color:#fff;
        display:flex; align-items:center; justify-content:space-between;
    }
    .mov-modal-header-ingreso { background:linear-gradient(135deg,#059669,#10b981); }
    .mov-modal-header-egreso  { background:linear-gradient(135deg,#dc2626,#ef4444); }
    .mov-modal-body { padding:1.5rem; }
    .tipo-pill {
        display:inline-flex; align-items:center; gap:.4rem;
        padding:.3rem .85rem; border-radius:99px; font-size:.78rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.04em;
    }
    .tipo-pill-ingreso { background:rgba(16,185,129,.15); color:#059669; }
    .tipo-pill-egreso  { background:rgba(239,68,68,.15);  color:#dc2626; }
    /* Receipt button in table */
    .btn-recibo {
        display:inline-flex; align-items:center; justify-content:center;
        width:1.75rem; height:1.75rem; border-radius:.375rem;
        color:var(--text-muted); transition:all .15s;
    }
    .btn-recibo:hover { background:var(--surface2); color:var(--primary); }
</style>
@endpush

@section('content')

    {{-- Flash de éxito con link a recibo --}}
    @if(session('success'))
        <div class="flash-success" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
            <span>{{ session('success') }}</span>
            @if(session('recibo_id'))
                <a href="{{ route('finance.caja.recibo', [$caja->codigo, session('recibo_id')]) }}"
                   target="_blank"
                   class="btn btn-ghost text-xs py-1"
                   style="border-color:currentColor;">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    Descargar recibo
                </a>
            @endif
        </div>
    @endif
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('finance.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Finanzas
            </a>
            <div>
                <h2 class="text-xl font-bold">{{ $caja->nombre }}</h2>
                <div class="text-xs mt-1" style="color:var(--text-muted)">{{ $caja->descripcion }}</div>
            </div>
        </div>

        {{-- Botones INGRESO / EGRESO con modal Alpine.js --}}
        <div class="flex gap-2" x-data="movModal()">
            <button type="button" class="btn btn-success" @click="abrir('INGRESO')">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Agregar efectivo
            </button>
            <button type="button" class="btn btn-danger" @click="abrir('EGRESO')">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                </svg>
                Quitar efectivo
            </button>

            {{-- ── Modal ── --}}
            <div class="mov-modal-overlay" x-show="show" x-transition.opacity @click.self="show=false" style="display:none">
                <div class="mov-modal-box" @click.stop x-transition.scale>

                    {{-- Header dinámico según tipo --}}
                    <div class="mov-modal-header" :class="tipo === 'INGRESO' ? 'mov-modal-header-ingreso' : 'mov-modal-header-egreso'">
                        <div>
                            <div class="font-bold text-base" x-text="tipo === 'INGRESO' ? '↑ Agregar efectivo' : '↓ Retirar efectivo'"></div>
                            <div class="text-xs opacity-75 mt-0.5">{{ $caja->nombre }}</div>
                        </div>
                        <button type="button" @click="show=false" class="opacity-75 hover:opacity-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="mov-modal-body">
                        <form method="POST" action="{{ route('finance.caja.movimiento', $caja->codigo) }}">
                            @csrf
                            <input type="hidden" name="tipo" :value="tipo">

                            {{-- Indicador de tipo --}}
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
                                            @foreach(['USD','PYG','BRL'] as $m)
                                                <option value="{{ $m }}" {{ $m === $caja->moneda_principal ? 'selected' : '' }}>{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Monto *</label>
                                        <input type="number" name="monto" class="form-input" step="0.01" min="0.01"
                                               required placeholder="0.00" x-ref="montoInput">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Concepto / Descripción *</label>
                                    <input type="text" name="concepto" class="form-input" required maxlength="300"
                                           :placeholder="tipo === 'INGRESO'
                                               ? 'Ej: Reposición caja chica, aporte inicial...'
                                               : 'Ej: Gastos de oficina, compra de suministros...'">
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
        </div>
    </div>

    {{-- ── Stats ──────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card {{ $saldo['saldo_usd'] < 0 ? 'danger' : '' }}">
            <div class="stat-label">Saldo actual (USD)</div>
            <div class="stat-value {{ $saldo['saldo_usd'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                $ {{ number_format($saldo['saldo_usd'], 2, ',', '.') }}
            </div>
            <div class="text-xs mt-1" style="color:var(--text-muted)">Equivalente USD</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Saldo actual (PYG)</div>
            <div class="stat-value text-blue-500">
                Gs {{ number_format($saldo['saldo_pyg'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-xs mt-1" style="color:var(--text-muted)">Solo movimientos en PYG</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total ingresos (período)</div>
            <div class="stat-value text-green-500">$ {{ number_format($totalesPeriodo->ingresos_usd ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total egresos (período)</div>
            <div class="stat-value text-red-500">$ {{ number_format($totalesPeriodo->egresos_usd ?? 0, 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- ── Card principal ─────────────────────────────────────────────────── --}}
    <div class="erp-card">

        {{-- Filtros --}}
        <div class="erp-card-header flex-wrap gap-3">
            <h2>Movimientos</h2>
            <form method="GET" class="flex flex-wrap gap-2 items-end">
                <div>
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-input text-xs py-1.5" value="{{ $desde }}">
                </div>
                <div>
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" class="form-input text-xs py-1.5" value="{{ $hasta }}">
                </div>
                <div>
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-input text-xs py-1.5">
                        <option value="">Todos</option>
                        <option value="INGRESO" {{ $tipoFil === 'INGRESO' ? 'selected' : '' }}>Ingresos</option>
                        <option value="EGRESO"  {{ $tipoFil === 'EGRESO'  ? 'selected' : '' }}>Egresos</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-ghost text-xs py-1.5">Filtrar</button>
                <a href="{{ route('finance.caja.reporte', [$caja->codigo, 'desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipoFil]) }}"
                   target="_blank"
                   class="btn btn-primary text-xs py-1.5" title="Descargar reporte PDF">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    PDF
                </a>
            </form>
        </div>

        {{-- Totales del período --}}
        <div class="grid grid-cols-3 text-center border-b" style="border-color:var(--border)">
            <div class="py-3 border-r" style="border-color:var(--border)">
                <div class="text-xs" style="color:var(--text-muted)">Ingresos período</div>
                <div class="font-bold text-green-500">$ {{ number_format($totalesPeriodo->ingresos_usd ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="py-3 border-r" style="border-color:var(--border)">
                <div class="text-xs" style="color:var(--text-muted)">Egresos período</div>
                <div class="font-bold text-red-500">$ {{ number_format($totalesPeriodo->egresos_usd ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="py-3">
                <div class="text-xs" style="color:var(--text-muted)">Neto período</div>
                @php $neto = ($totalesPeriodo->ingresos_usd ?? 0) - ($totalesPeriodo->egresos_usd ?? 0); @endphp
                <div class="font-bold {{ $neto >= 0 ? 'text-green-500' : 'text-red-500' }}">
                    $ {{ number_format($neto, 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Origen</th>
                        <th class="text-right">Monto</th>
                        <th class="text-right">USD</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center w-12" title="Descargar recibo">PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $m)
                        <tr>
                            <td class="whitespace-nowrap text-xs" style="color:var(--text-muted)">
                                {{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <div class="text-sm font-medium">{{ $m->concepto }}</div>
                            </td>
                            <td>
                                @if($m->ref_type)
                                    @php
                                        $origenLabel = match($m->ref_type) {
                                            'venta'            => '🛒 Venta',
                                            'cuota'            => '📅 Cuota',
                                            'factura_gasto_op' => '🧾 Gasto local',
                                            'factura_vehiculo' => '🚛 Gasto vehículo',
                                            'transferencia'    => '↔ Transferencia',
                                            'manual'           => '✏️ Manual',
                                            default            => $m->ref_type,
                                        };
                                    @endphp
                                    <span class="ref-chip">{{ $origenLabel }}</span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td class="text-right text-sm font-mono">
                                {{ number_format($m->monto, 2, ',', '.') }} {{ $m->moneda }}
                            </td>
                            <td class="text-right text-sm font-mono font-semibold">
                                $ {{ number_format($m->monto_usd, 2, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge-status {{ $m->tipo === 'INGRESO' ? 'badge-ingreso' : 'badge-egreso' }}">
                                    {{ $m->tipo === 'INGRESO' ? '↑ Ingreso' : '↓ Egreso' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('finance.caja.recibo', [$caja->codigo, $m->id]) }}"
                                   target="_blank"
                                   title="Imprimir comprobante"
                                   class="btn-recibo">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6.72 13.821V21m0 0h10.56m-10.56 0V13.821m10.56 7.179V13.821M17.28 13.821H6.72m10.56 0V11.25a2.25 2.25 0 0 0-2.25-2.25h-9a2.25 2.25 0 0 0-2.25 2.25v2.571m10.56 0h1.78c.945 0 1.808.539 2.212 1.392l.232.49a2.25 2.25 0 0 1 0 1.888l-.232.49a2.25 2.25 0 0 1-2.212 1.392h-1.78m0-7.429V11.25m-10.56 0V11.25"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-sm" style="color:var(--text-muted)">
                                Sin movimientos en el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($movimientos->hasPages())
            <div class="p-4 border-t" style="border-color:var(--border)">
                {{ $movimientos->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('movModal', () => ({
        show: false,
        tipo: 'INGRESO',
        abrir(tipo) {
            this.tipo = tipo;
            this.show = true;
            this.$nextTick(() => this.$refs.montoInput?.focus());
        },
    }));
});
</script>
@endpush
