@extends('layouts.app')
@section('title', 'Centro de Notificaciones')
@section('page-title', 'Centro de Notificaciones')

@section('content')

    {{-- ── Stats resumen ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#ef4444">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                En Mora
            </div>
            <div class="text-2xl font-bold" style="color:#ef4444">{{ $cuotasMora->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#f59e0b">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Cobrar Hoy
            </div>
            <div class="text-2xl font-bold" style="color:#f59e0b">{{ $cuotasHoy->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#3b82f6">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Próx. 7 días
            </div>
            <div class="text-2xl font-bold" style="color:#3b82f6">{{ $cuotasProximas->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#8b5cf6">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Facturas Pagar
            </div>
            <div class="text-2xl font-bold" style="color:#8b5cf6">{{ $facturasPagar->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4 col-span-2 sm:col-span-1" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#10b981">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                </svg>
                A Declarar
            </div>
            <div class="text-2xl font-bold" style="color:#10b981">{{ $facturasDeclarar->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#f59e0b">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                Stock Bajo
            </div>
            <div class="text-2xl font-bold" style="color:#f59e0b">{{ $repuestosBajos->total() }}</div>
        </div>
    </div>

    {{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
    <div class="notif-tabs" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
        <button class="notif-tab-btn active"
            style="border:1px solid #ef4444; color:#ef4444; background:transparent; padding:0.5rem 1rem; border-radius:6px; font-weight:600"
            onclick="showTab('mora',this)">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            En Mora ({{ $cuotasMora->total() }})
        </button>
        <button class="notif-tab-btn"
            style="border:1px solid #f59e0b; color:#f59e0b; background:transparent; padding:0.5rem 1rem; border-radius:6px; font-weight:600"
            onclick="showTab('hoy',this)">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
            Cobros de Hoy ({{ $cuotasHoy->total() }})
        </button>
        <button class="notif-tab-btn"
            style="border:1px solid #3b82f6; color:#3b82f6; background:transparent; padding:0.5rem 1rem; border-radius:6px; font-weight:600"
            onclick="showTab('proximas',this)">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Próx. 7 Días ({{ $cuotasProximas->total() }})
        </button>
        <button class="notif-tab-btn"
            style="border:1px solid #8b5cf6; color:#8b5cf6; background:transparent; padding:0.5rem 1rem; border-radius:6px; font-weight:600"
            onclick="showTab('facturas_pagar',this)">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            Facturas Pagar ({{ $facturasPagar->total() }})
        </button>
        <button class="notif-tab-btn"
            style="border:1px solid #10b981; color:#10b981; background:transparent; padding:0.5rem 1rem; border-radius:6px; font-weight:600"
            onclick="showTab('facturas_declarar',this)">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
            </svg>
            A Declarar ({{ $facturasDeclarar->total() }})
        </button>
        <button class="notif-tab-btn"
            style="border:1px solid #f59e0b; color:#f59e0b; background:transparent; padding:0.5rem 1rem; border-radius:6px; font-weight:600"
            onclick="showTab('stock',this)">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            Stock Bajo ({{ $repuestosBajos->total() }})
        </button>
    </div>

    {{-- ── Tab: En Mora ────────────────────────────────────────────────────── --}}
    <div id="panel-mora" class="notif-panel active">
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 style="color:#ef4444; display: flex; align-items: center; gap: 0.5rem;">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Cuotas en Mora
                </h2>
                <span style="font-size:.75rem;color:var(--text-muted)">Cuotas que ya vencieron</span>
            </div>
            @if($cuotasMora->isEmpty())
                <div
                    style="padding:2rem;text-align:center;color:var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    No hay cuotas en mora
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>N° Venta</th>
                                <th>Cuota</th>
                                <th>Vencimiento</th>
                                <th>Días mora</th>
                                <th>Monto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuotasMora as $row)
                                @php
                                    $dias = \Carbon\Carbon::parse($row->fecha_vencimiento)->diffInDays(\Carbon\Carbon::today());
                                    $monto = (float) $row->capital + (float) $row->interes + (float) $row->interes_mora;
                                @endphp
                                <tr>
                                    <td style="font-weight:600">{{ $row->cliente_nombre }}</td>
                                    <td>{{ $row->numero_venta }}</td>
                                    <td>{{ $row->numero_cuota }}/{{ $row->total_cuotas }}</td>
                                    <td style="color:#ef4444;font-weight:600">
                                        {{ \Carbon\Carbon::parse($row->fecha_vencimiento)->format('d/m/Y') }}
                                    </td>
                                    <td><span class="dias-mora"
                                            style="background:rgba(239,68,68,.15);color:#ef4444;padding:0.2rem 0.6rem;border-radius:999px;font-size:0.75rem;font-weight:bold;">{{ $dias }}d</span>
                                    </td>
                                    <td style="font-weight:600;color:#ef4444">{{ $row->moneda }}
                                        {{ number_format($monto, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('planes_cuotas.show', $row->plan_id) }}" class="btn btn-ghost"
                                            style="font-size:.72rem;padding:.3rem .7rem">Ver Plan</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:.75rem 1rem">{{ $cuotasMora->appends(request()->except('mora_page'))->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ── Tab: Hoy ────────────────────────────────────────────────────────── --}}
    <div id="panel-hoy" class="notif-panel" style="display:none">
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 style="color:#f59e0b; display: flex; align-items: center; gap: 0.5rem;">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    Cuotas a Cobrar Hoy
                </h2>
                <span style="font-size:.75rem;color:var(--text-muted)">Cuotas que vencen el día de hoy</span>
            </div>
            @if($cuotasHoy->isEmpty())
                <div
                    style="padding:2rem;text-align:center;color:var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    No hay cobros para hoy
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>N° Venta</th>
                                <th>Cuota</th>
                                <th>Vencimiento</th>
                                <th>Monto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuotasHoy as $row)
                                @php $monto = (float) $row->capital + (float) $row->interes; @endphp
                                <tr>
                                    <td style="font-weight:600">{{ $row->cliente_nombre }}</td>
                                    <td>{{ $row->numero_venta }}</td>
                                    <td>{{ $row->numero_cuota }}/{{ $row->total_cuotas }}</td>
                                    <td style="color:#f59e0b;font-weight:600">HOY</td>
                                    <td style="font-weight:600">{{ $row->moneda }} {{ number_format($monto, 2, ',', '.') }}</td>
                                    <td><a href="{{ route('planes_cuotas.show', $row->plan_id) }}" class="btn btn-ghost"
                                            style="font-size:.72rem;padding:.3rem .7rem">Ver Plan</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:.75rem 1rem">{{ $cuotasHoy->appends(request()->except('hoy_page'))->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ── Tab: Próximas ───────────────────────────────────────────────────── --}}
    <div id="panel-proximas" class="notif-panel" style="display:none">
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 style="color:#3b82f6; display: flex; align-items: center; gap: 0.5rem;">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Cuotas Próximas a Vencer
                </h2>
                <span style="font-size:.75rem;color:var(--text-muted)">Próximos 7 días</span>
            </div>
            @if($cuotasProximas->isEmpty())
                <div
                    style="padding:2rem;text-align:center;color:var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    No hay cuotas próximas
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>N° Venta</th>
                                <th>Cuota</th>
                                <th>Vencimiento</th>
                                <th>Falta</th>
                                <th>Monto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuotasProximas as $row)
                                @php
                                    $dias = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($row->fecha_vencimiento));
                                    $monto = (float) $row->capital + (float) $row->interes;
                                @endphp
                                <tr>
                                    <td style="font-weight:600">{{ $row->cliente_nombre }}</td>
                                    <td>{{ $row->numero_venta }}</td>
                                    <td>{{ $row->numero_cuota }}/{{ $row->total_cuotas }}</td>
                                    <td style="color:#3b82f6;font-weight:600">
                                        {{ \Carbon\Carbon::parse($row->fecha_vencimiento)->format('d/m/Y') }}
                                    </td>
                                    <td><span
                                            style="background:rgba(59,130,246,.15);color:#3b82f6;padding:.2rem .5rem;border-radius:999px;font-size:.7rem;font-weight:bold">{{ $dias }}d</span>
                                    </td>
                                    <td style="font-weight:600">{{ $row->moneda }} {{ number_format($monto, 2, ',', '.') }}</td>
                                    <td><a href="{{ route('planes_cuotas.show', $row->plan_id) }}" class="btn btn-ghost"
                                            style="font-size:.72rem;padding:.3rem .7rem">Ver Plan</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:.75rem 1rem">{{ $cuotasProximas->appends(request()->except('prox_page'))->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ── Tab: Facturas a Pagar ───────────────────────────────────────────── --}}
    <div id="panel-facturas_pagar" class="notif-panel" style="display:none">
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 style="color:#8b5cf6; display: flex; align-items: center; gap: 0.5rem;">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    Facturas a Pagar
                </h2>
                <span style="font-size:.75rem;color:var(--text-muted)">Facturas de proveedores pendientes de pago</span>
            </div>
            @if($facturasPagar->isEmpty())
                <div
                    style="padding:2rem;text-align:center;color:var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    No hay facturas pendientes
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>N° Factura</th>
                                <th>Fecha</th>
                                <th>Total USD</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facturasPagar as $row)
                                <tr>
                                    <td style="font-weight:600">{{ $row->proveedor_nombre }}</td>
                                    <td>{{ $row->numero_factura }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row->fecha_factura)->format('d/m/Y') }}</td>
                                    <td style="font-weight:600;color:#8b5cf6">{{ $row->moneda }}
                                        {{ number_format($row->total_usd, 2, ',', '.') }}
                                    </td>
                                    <td><a href="{{ route('facturas.show', $row->factura_id) }}" class="btn btn-ghost"
                                            style="font-size:.72rem;padding:.3rem .7rem">Ver</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:.75rem 1rem">{{ $facturasPagar->appends(request()->except('pagar_page'))->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ── Tab: Facturas a Declarar ────────────────────────────────────────── --}}
    <div id="panel-facturas_declarar" class="notif-panel" style="display:none">
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 style="color:#10b981; display: flex; align-items: center; gap: 0.5rem;">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                    </svg>
                    Facturas a Declarar (Este mes)
                </h2>
                <span style="font-size:.75rem;color:var(--text-muted)">Facturas del mes actual</span>
            </div>
            @if($facturasDeclarar->isEmpty())
                <div
                    style="padding:2rem;text-align:center;color:var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    No hay facturas para declarar este mes
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>N° Factura</th>
                                <th>Fecha</th>
                                <th>Total USD</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facturasDeclarar as $row)
                                <tr>
                                    <td style="font-weight:600">{{ $row->proveedor_nombre }}</td>
                                    <td>{{ $row->numero_factura }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row->fecha_factura)->format('d/m/Y') }}</td>
                                    <td style="font-weight:600">{{ $row->moneda }} {{ number_format($row->total_usd, 2, ',', '.') }}
                                    </td>
                                    <td><a href="{{ route('facturas.show', $row->factura_id) }}" class="btn btn-ghost"
                                            style="font-size:.72rem;padding:.3rem .7rem">Ver</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:.75rem 1rem">{{ $facturasDeclarar->appends(request()->except('declarar_page'))->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ── Tab: Stock Bajo ────────────────────────────────────────────────── --}}
    <div id="panel-stock" class="notif-panel" style="display:none">
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 style="color:#f59e0b; display: flex; align-items: center; gap: 0.5rem;">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    Repuestos con Stock Bajo Mínimo
                </h2>
                <span style="font-size:.75rem;color:var(--text-muted)">Productos que requieren reposición</span>
            </div>
            @if($repuestosBajos->isEmpty())
                <div
                    style="padding:2rem;text-align:center;color:var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Todos los repuestos tienen stock suficiente
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($repuestosBajos as $row)
                                <tr>
                                    <td style="font-weight:600; color:var(--accent)">{{ $row->codigo }}</td>
                                    <td>{{ $row->descripcion }}</td>
                                    <td style="font-weight:700; color:#ef4444">
                                        {{ number_format((float)$row->stock_actual, 2, ',', '.') }}
                                    </td>
                                    <td style="font-weight:600">
                                        {{ number_format((float)$row->stock_minimo, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('repuestos.edit', $row->id) }}" class="btn btn-ghost"
                                            style="font-size:.72rem;padding:.3rem .7rem">Editar / Reponer</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:.75rem 1rem">{{ $repuestosBajos->appends(request()->except('stock_page'))->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function showTab(name, btn) {
            document.querySelectorAll('.notif-panel').forEach(function (p) { p.style.display = 'none'; });
            document.querySelectorAll('.notif-tab-btn').forEach(function (b) {
                b.classList.remove('active');
                b.style.background = 'transparent';
            });

            document.getElementById('panel-' + name).style.display = 'block';
            btn.classList.add('active');
            btn.style.background = btn.style.color;
            btn.style.color = 'white';
        }

        // Inicializar tab activa
        document.addEventListener('DOMContentLoaded', () => {
            let activeBtn = document.querySelector('.notif-tab-btn.active');
            if (activeBtn) {
                activeBtn.style.background = activeBtn.style.color;
                activeBtn.style.color = 'white';
            }
        });
    </script>

@endsection