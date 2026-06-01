@extends('layouts.app')
@section('title', 'Detalle Venta')
@section('page-title', 'Detalle de Venta')

@section('content')
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>@endif

    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('ventas.index') }}" class="p-2.5 rounded-xl bg-surface2 border border-white/5 text-muted-foreground hover:text-primary transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Operación #{{ $venta->numero_venta }}</h1>
                    @php $cls = match ($venta->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO' => 'badge-vendido', 'RESERVADO' => 'badge-preparacion', default => 'badge-toma'}; @endphp
                    <span class="badge-status {{ $cls }} !text-[0.6rem] !px-2 !font-black uppercase tracking-widest shadow-lg">{{ $venta->estado }}</span>
                </div>
                <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Fecha de Registro: {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($venta->estado !== 'CANCELADO')
            <a href="{{ route('ventas.edit', $venta->id) }}" class="btn btn-ghost flex-1 md:flex-none py-3 px-5 rounded-xl border border-accent/30 text-accent hover:bg-accent/10 transition-all text-xs font-black uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                Editar
            </a>
            <button type="button" onclick="document.getElementById('modal-cancelar-venta').classList.remove('hidden')"
                class="btn btn-ghost flex-1 md:flex-none py-3 px-5 rounded-xl border border-red-500/30 text-red-400 hover:bg-red-500/10 transition-all text-xs font-black uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Cancelar Venta
            </button>
            @endif
            <a href="{{ route('ventas.imprimir', $venta->id) }}" target="_blank" class="btn btn-primary flex-1 md:flex-none py-3 px-6 rounded-xl shadow-lg shadow-primary/25 border-b-2 border-primary-hover active:translate-y-0.5 transition-all text-xs font-black uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path d="M6.72 13.844 2.4 12l4.32-1.844L8.564 5.84l1.844-4.32L12.252 5.84l4.32 1.844-4.32 1.844-1.844 4.322-1.844-4.322Z" />
                    <path d="M17.114 22.844 12.8 21l4.32-1.844L18.964 14.84l1.844-4.32L22.652 14.84l4.32 1.844-4.32 1.844-1.844 4.322-1.844-4.322Z" />
                </svg>
                Imprimir Nota
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Vehículo y Cliente --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Info Vehículo --}}
                <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125v-10.5a1.125 1.125 0 0 0-1.125-1.125H3.375a1.125 1.125 0 0 0-1.125 1.125v4.5m17.25 2.25h-5.625c-.621 0-1.125.504-1.125 1.125v3.375m7.5-3.375h-7.5m7.5-1.125h-7.5" />
                        </svg>
                    </div>
                    <div class="erp-card-header !bg-transparent border-b border-white/5">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Unidad Principal</h2>
                    </div>
                    <div class="erp-card-body">
                        @if($venta->vehiculo)
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125v-10.5a1.125 1.125 0 0 0-1.125-1.125H3.375a1.125 1.125 0 0 0-1.125 1.125v4.5m17.25 2.25h-5.625c-.621 0-1.125.504-1.125 1.125v3.375m7.5-3.375h-7.5m7.5-1.125h-7.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-black text-white uppercase tracking-tight">{{ $venta->vehiculo->marca }} {{ $venta->vehiculo->modelo }}</div>
                                        <div class="text-[0.6rem] font-black text-muted-foreground uppercase opacity-60">Año {{ $venta->vehiculo->anio }}</div>
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-surface2 border border-white/5">
                                    <span class="text-[0.55rem] text-muted-foreground uppercase font-black tracking-widest block mb-1">Número de Chasis</span>
                                    <span class="text-sm font-mono font-black text-accent">{{ $venta->vehiculo->numero_chasis ?? '—' }}</span>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-center py-6 text-muted-foreground/30 italic">
                                <svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span class="text-[0.6rem] uppercase font-black">Sin vehículo principal</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Info Cliente --}}
                <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div class="erp-card-header !bg-transparent border-b border-white/5">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Comprador</h2>
                    </div>
                    <div class="erp-card-body">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-black text-white uppercase tracking-tight truncate">{{ $venta->cliente->razon_social }}</div>
                                    <div class="text-[0.6rem] font-black text-muted-foreground uppercase opacity-60">RUC: {{ $venta->cliente->ruc ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-[0.7rem] font-black text-accent-light bg-accent/5 p-2 px-3 rounded-lg border border-accent/10">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                <span>{{ $venta->cliente->telefono ?: 'No especificado' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items de la Venta --}}
            <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5">
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Desglose de Productos / Servicios</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr class="!bg-surface3/5 text-[0.55rem]">
                                <th class="!pl-6">Descripción</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-right">Precio Unit.</th>
                                <th class="text-right !pr-6">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr class="border-b border-white/5 last:border-0">
                                    <td class="!pl-6">
                                        <div class="text-xs font-bold text-white tracking-tight">{{ $item->descripcion }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-[0.65rem] font-black bg-surface2 px-2 py-0.5 rounded-lg border border-white/5">{{ number_format($item->cantidad, 0) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="text-xs text-muted-foreground">${{ number_format($item->precio_unitario_usd, 2, ',', '.') }}</span>
                                    </td>
                                    <td class="text-right !pr-6">
                                        <span class="text-xs font-black text-white">${{ number_format($item->subtotal_usd, 2, ',', '.') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- Versión Móvil de los Items --}}
                <div class="md:hidden divide-y divide-white/5">
                    @foreach($items as $item)
                        <div class="p-4 flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-black text-white uppercase truncate">{{ $item->descripcion }}</div>
                                <div class="text-[0.6rem] text-muted-foreground font-black mt-1">{{ number_format($item->cantidad, 0) }} x ${{ number_format($item->precio_unitario_usd, 2, ',', '.') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-black text-accent">${{ number_format($item->subtotal_usd, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar Financiero --}}
        <div class="space-y-6">
            <div class="erp-card !bg-primary/5 !border-primary/20 relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/10 rounded-full blur-3xl"></div>
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-primary">Liquidación Final</h2>
                </div>
                <div class="erp-card-body space-y-4">
                    <div class="text-center py-4">
                        <span class="text-[0.55rem] text-muted-foreground uppercase font-black tracking-widest block mb-1">Monto Total de Operación</span>
                        <div class="flex items-baseline justify-center gap-1.5">
                            <span class="text-3xl font-black text-white tracking-tighter">$ {{ number_format($venta->precio_venta_usd, 2, ',', '.') }}</span>
                            <span class="text-xs font-black text-primary uppercase">USD</span>
                        </div>
                        @if($venta->moneda_venta !== 'USD')
                            <div class="mt-2 text-xs font-mono font-black text-muted-foreground/60 uppercase">
                                ≈ {{ number_format($venta->precio_venta_moneda ?? 0, 2, ',', '.') }} {{ $venta->moneda_venta }}
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2 border-t border-white/5 pt-4">
                        <div class="flex items-center justify-between text-[0.65rem]">
                            <span class="text-muted-foreground font-black uppercase tracking-tighter">Valor en Libros</span>
                            <span class="text-white font-mono font-bold">$ {{ number_format($venta->valor_libro_snapshot, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl {{ $rentabilidad >= 0 ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-500' }} border">
                            <span class="text-[0.55rem] font-black uppercase tracking-widest">Utilidad Estimada</span>
                            <span class="text-sm font-black tracking-tight">$ {{ number_format($rentabilidad, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Planes de Pago --}}
            @if($plan)
                <div class="erp-card !bg-surface/60 !backdrop-blur border-white/5">
                    <div class="erp-card-header !bg-transparent border-b border-white/5 flex items-center justify-between">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-accent">Línea de Crédito</h2>
                        <span class="badge-status badge-preparacion !text-[0.55rem] !px-1.5 uppercase font-black leading-none">{{ $plan->tipo_plan }}</span>
                    </div>
                    <div class="erp-card-body p-4">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-[0.5rem] text-muted-foreground uppercase font-black block leading-none mb-1">Estado Plan</span>
                                <span class="text-xs font-black text-white uppercase tracking-tighter">Activo con {{ $cuotas->count() }} cuotas</span>
                            </div>
                            <a href="{{ route('planes_cuotas.show', $plan->id) }}" class="p-2 rounded-lg bg-surface3/50 text-white hover:text-accent transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                        <div class="space-y-2">
                             @foreach($cuotas->take(3) as $c)
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-surface2/50 border border-white/5">
                                    <div class="text-[0.6rem] font-black text-muted-foreground uppercase leading-none">
                                        Cuota {{ $c->numero_cuota }}
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[0.65rem] font-black text-white leading-none mb-0.5">${{ number_format($c->capital + $c->interes, 2, ',', '.') }}</div>
                                        @php $cs = match ($c->estado) { 'PAGADA' => 'text-green-500', 'VENCIDA', 'EN_MORA' => 'text-red-500', default => 'text-amber-500'}; @endphp
                                        <div class="text-[0.5rem] font-black uppercase tracking-widest {{ $cs }}">{{ $c->estado }}</div>
                                    </div>
                                </div>
                             @endforeach
                            @if($cuotas->count() > 3)
                                <a href="{{ route('planes_cuotas.show', $plan->id) }}" class="block text-center py-2 text-[0.55rem] font-black text-accent uppercase tracking-[0.2em] hover:opacity-70 transition-opacity">Ver plan completo</a>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="p-5 rounded-3xl border border-dashed border-white/10 flex flex-col items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-surface2 flex items-center justify-center text-muted-foreground/30">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                        </svg>
                    </div>
                    <p class="text-[0.6rem] text-muted-foreground uppercase font-black tracking-widest text-center">Operación de Contado Sin Plan de Cuotas</p>
                    <a href="{{ route('planes_cuotas.create', $venta->id) }}" class="btn btn-ghost !text-accent-light border-accent/20 hover:!bg-accent/10 px-4 py-2 rounded-xl text-[0.6rem] font-black uppercase tracking-wider">Habilitar Crédito</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Pagos Iniciales --}}
    @if($pagos->where('tipo_pago', '!=', 'PLAN_CUOTAS')->count() > 0)
        <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 mb-8">
            <div class="erp-card-header !bg-transparent border-b border-white/5">
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Flujos de Caja Iniciales</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr class="!bg-surface3/5 text-[0.55rem]">
                            <th class="!pl-6">Método de Pago</th>
                            <th>Monto USD</th>
                            <th>Fecha</th>
                            <th class="!pr-6">Referencia / Obs.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pagos->where('tipo_pago', '!=', 'PLAN_CUOTAS') as $p)
                            <tr class="border-b border-white/5 last:border-0 hover:bg-white/5">
                                <td class="!pl-6">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-accent"></div>
                                        <span class="text-xs font-black text-white uppercase tracking-tighter">{{ str_replace('_', ' ', $p->tipo_pago) }}</span>
                                    </div>
                                </td>
                                <td><span class="text-xs font-black text-accent">${{ number_format($p->monto_usd, 2, ',', '.') }}</span></td>
                                <td><span class="text-[0.7rem] font-bold text-muted-foreground">{{ \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') }}</span></td>
                                <td class="!pr-6"><span class="text-[0.7rem] text-muted-foreground opacity-60 italic">{{ $p->referencia_bancaria ?: $p->observaciones ?: 'Sin referencias' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Documentos Adjuntos --}}
    <div class="mt-8">
        @include('partials.documentos', [
            'documentos' => $documentos ?? collect(),
            'documentableType' => 'ventas',
            'documentableId' => $venta->id,
        ])
    </div>

    @if(session('show_print_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (confirm('¿Desea imprimir la Nota de Venta ahora?')) {
                    window.open('{{ route('ventas.imprimir', $venta->id) }}', '_blank');
                }
            });
        </script>
    @endif
{{-- ── Modal Cancelar Venta ── --}}
<div id="modal-cancelar-venta" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
    <div class="w-full max-w-md bg-surface2 border border-white/10 rounded-2xl shadow-2xl p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center text-red-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-black text-white uppercase tracking-widest">Cancelar Venta</h3>
                <p class="text-[0.65rem] text-muted-foreground mt-0.5">Operación #{{ $venta->numero_venta }}</p>
            </div>
        </div>

        <div class="bg-red-500/5 border border-red-500/20 rounded-xl p-4 mb-5 space-y-1.5">
            <p class="text-xs text-red-300 font-semibold">Esta acción realizará lo siguiente:</p>
            <ul class="text-[0.65rem] text-muted-foreground space-y-1 list-disc list-inside">
                <li>Devuelve el vehículo a estado DISPONIBLE</li>
                <li>Revierte los movimientos de caja</li>
                <li>Anula los detalles de pago</li>
                @if($plan)
                <li>Anula el plan de cuotas y sus {{ $cuotas->count() }} cuota(s) pendientes</li>
                @endif
            </ul>
        </div>

        <form action="{{ route('ventas.destroy', $venta->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="mb-5">
                <label class="block text-xs font-bold text-muted-foreground uppercase tracking-widest mb-2">Motivo de cancelación <span class="text-red-400">*</span></label>
                <textarea name="motivo" required rows="3" placeholder="Describe brevemente el motivo de la cancelación..."
                    class="w-full bg-surface border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-muted-foreground focus:outline-none focus:border-primary/50 resize-none"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('modal-cancelar-venta').classList.add('hidden')"
                    class="flex-1 py-2.5 rounded-xl border border-white/10 text-muted-foreground hover:text-white hover:border-white/20 transition-all text-xs font-black uppercase tracking-wider">
                    Volver
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white transition-all text-xs font-black uppercase tracking-wider shadow-lg shadow-red-900/30">
                    Confirmar cancelación
                </button>
            </div>
        </form>
    </div>
</div>
@endsection