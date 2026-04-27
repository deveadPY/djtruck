@extends('layouts.app')
@section('title', 'Facturas y Gastos')
@section('page-title', 'Facturas y Gastos')

@section('content')
    @if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>@endif

    {{-- ── Cabecera y Acciones ── --}}
    <div class="space-y-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-500 md:hidden">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Libro de Egresos</h1>
                    <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold hidden md:block">Gestión integral de facturas, gastos operativos y compras de stock</p>
                </div>
            </div>

            <a href="{{ route('facturas.create') }}" class="btn btn-primary flex-1 md:flex-none py-3 px-6 rounded-xl shadow-lg shadow-primary/25 border-b-2 border-primary-hover active:translate-y-0.5 transition-all text-xs font-black uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Cargar Factura
            </a>
        </div>

        {{-- Filtros Avanzados Glassmorphism --}}
        <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 p-4">
            <form method="GET" action="{{ route('facturas.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Proveedores --}}
                <div class="relative">
                    <select name="proveedor_id" onchange="this.form.submit()" 
                        class="form-input !bg-surface h-11 text-[0.65rem] rounded-xl border-white/5 focus:ring-primary/20 transition-all font-black uppercase tracking-widest w-full">
                        <option value="">TODOS LOS PROVEEDORES</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}" @selected(request('proveedor_id') == $prov->id)>{{ $prov->razon_social }}</option>
                        @endforeach
                    </select>
                    <span class="absolute -top-2 left-3 px-1 bg-surface text-[0.5rem] font-black text-muted-foreground uppercase tracking-widest">Entidad</span>
                </div>

                {{-- Rango de Fechas --}}
                <div class="relative flex-1">
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" onchange="this.form.submit()"
                        class="form-input !bg-surface h-11 text-[0.65rem] rounded-xl border-white/5 focus:ring-primary/20 transition-all font-black uppercase tracking-tighter w-full">
                    <span class="absolute -top-2 left-3 px-1 bg-surface text-[0.5rem] font-black text-muted-foreground uppercase tracking-widest">Desde</span>
                </div>

                <div class="relative flex-1">
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" onchange="this.form.submit()"
                        class="form-input !bg-surface h-11 text-[0.65rem] rounded-xl border-white/5 focus:ring-primary/20 transition-all font-black uppercase tracking-tighter w-full">
                    <span class="absolute -top-2 left-3 px-1 bg-surface text-[0.5rem] font-black text-muted-foreground uppercase tracking-widest">Hasta</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-ghost !bg-primary/10 !text-primary flex-1 h-11 rounded-xl font-black text-[0.65rem] uppercase tracking-widest">
                        Filtrar
                    </button>
                    @if(request()->hasAny(['proveedor_id', 'fecha_inicio', 'fecha_fin']))
                        <a href="{{ route('facturas.index') }}" class="btn btn-ghost !bg-red-500/5 !text-red-400 border-red-500/10 h-11 px-4 rounded-xl hover:!bg-red-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Vista Escritorio (Table Master) --}}
    <div class="hidden md:block erp-card !border-white/5 relative overflow-hidden">
        <div class="erp-card-header !bg-surface3/10">
            <div class="flex items-center gap-3 w-full">
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground/80">Monitor de Egresos Proyectados</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-border/50 to-transparent"></div>
                <div class="flex items-center gap-4">
                    <div class="flex flex-col items-end">
                        <span class="text-[0.5rem] font-black text-muted-foreground uppercase tracking-tighter">Venta de Vista</span>
                        <span class="text-xs font-black text-red-400">$ {{ number_format($facturas->sum('total_usd'), 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr class="text-[0.6rem] uppercase tracking-widest">
                        <th class="!pl-6">Emisión</th>
                        <th>Documento</th>
                        <th>Proveedor</th>
                        <th>Aplicación / Destino</th>
                        <th class="text-right">Importe USD</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right !pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facturas as $f)
                        <tr class="hover:bg-red-500/5 transition-all duration-200 group cursor-pointer" onclick="window.location='{{ route('facturas.show', $f->id) }}'">
                            <td class="!pl-6">
                                <div class="text-xs font-bold text-white/80">{{ \Carbon\Carbon::parse($f->fecha_factura)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="font-mono text-[0.68rem] font-black text-accent bg-surface2 px-2 py-1 rounded border border-white/5 w-fit">
                                    {{ $f->numero_factura }}
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('proveedores.show', $f->proveedor_id) }}" onclick="event.stopPropagation()" class="text-sm font-bold text-white hover:text-primary transition-colors decoration-primary/30">{{ $f->razon_social }}</a>
                            </td>
                            <td>
                                @if($f->destino === 'VEHICULO')
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 text-xs font-black text-accent uppercase tracking-tighter">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125v-10.5a1.125 1.125 0 0 0-1.125-1.125H3.375a1.125 1.125 0 0 0-1.125 1.125v4.5m17.25 2.25h-5.625c-.621 0-1.125.504-1.125 1.125v3.375m7.5-3.375h-7.5m7.5-1.125h-7.5" /></svg>
                                            Inversión Vehículo
                                        </div>
                                        <div class="text-[0.6rem] text-muted-foreground font-bold uppercase tracking-widest mt-0.5">{{ $f->marca }} {{ $f->modelo }} ({{ substr($f->numero_chasis, -6) }})</div>
                                    </div>
                                @elseif($f->destino === 'REPOSICION')
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 text-xs font-black text-amber-500 uppercase tracking-tighter">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="m20.25 7.5-.625 10.125a3.375 3.375 0 0 1-3.375 3.375H7.75a3.375 3.375 0 0 1-3.375-3.375L3.75 7.5M15 12a3 3 0 1 1-6 0m9.75-4.5h-13.5c-.612 0-1.118-.471-1.125-1.083L3.75 3h16.5l-.375 3.417c-.007.612-.513 1.083-1.125 1.083Z" /></svg>
                                            Reposición Stock
                                        </div>
                                        <div class="text-[0.6rem] text-muted-foreground font-bold uppercase tracking-widest mt-0.5">Asociado a Compra #{{ $f->compra_id }}</div>
                                    </div>
                                @else
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 text-xs font-black text-slate-400 uppercase tracking-tighter">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                            Gasto de Local / Admin
                                        </div>
                                        <div class="text-[0.6rem] text-muted-foreground font-bold uppercase tracking-widest mt-0.5">{{ $f->cuenta_gasto ?? 'General' }}</div>
                                    </div>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="font-black text-sm text-red-500 group-hover:scale-105 origin-right transition-transform inline-block">$ {{ number_format($f->total_usd, 2, ',', '.') }}</span>
                            </td>
                            <td class="text-center">
                                @php $cls = match ($f->estado) { 'APROBADA', 'PAGADA' => 'badge-disponible', 'ANULADA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                <span class="badge-status {{ $cls }} !text-[0.55rem] !px-2 !font-black uppercase tracking-widest">{{ $f->estado }}</span>
                            </td>
                            <td class="text-right !pr-6" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Ver --}}
                                    <a href="{{ route('facturas.show', $f->id) }}" title="Ver detalle"
                                       class="p-2 rounded-xl hover:bg-primary/10 text-muted-foreground hover:text-primary transition-all inline-flex">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>
                                    {{-- Eliminar --}}
                                    <button type="button" title="Eliminar"
                                        onclick="abrirModalEliminar({{ $f->id }}, '{{ $f->numero_factura }}', '{{ addslashes($f->razon_social) }}', '{{ number_format($f->total_usd, 2, ',', '.') }}')"
                                        class="p-2 rounded-xl hover:bg-red-500/10 text-muted-foreground hover:text-red-500 transition-all inline-flex">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-20">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <p class="font-black uppercase tracking-[0.2em] text-xs">Sin Facturas Registradas</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vista Móvil (Grid Cards) --}}
    <div class="md:hidden space-y-4">
        @forelse($facturas as $f)
            <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-surface2/40 backdrop-blur-md p-5 transition-all duration-300 active:scale-[0.98]" onclick="window.location='{{ route('facturas.show', $f->id) }}'">
                @php $cls = match ($f->estado) { 'APROBADA', 'PAGADA' => 'badge-disponible', 'ANULADA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                <div class="absolute top-0 right-0 px-3 py-1 bg-surface3 rounded-bl-xl border-l border-b border-white/5">
                    <span class="badge-status {{ $cls }} !text-[0.5rem] !px-1.5 !py-0 !font-black uppercase tracking-tighter">{{ $f->estado }}</span>
                </div>

                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl {{ $f->destino === 'VEHICULO' ? 'bg-accent/10 text-accent' : ($f->destino === 'REPOSICION' ? 'bg-amber-500/10 text-amber-500' : 'bg-slate-500/10 text-slate-400') }} flex flex-col items-center justify-center flex-shrink-0 shadow-inner">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($f->destino === 'VEHICULO') <path d="M8.25 18.75a1.5 1.5 0 0 1-3 0" /> @elseif($f->destino === 'REPOSICION') <path d="m20.25 7.5-.625 10.125" /> @else <path d="M3.75 21h16.5" /> @endif
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-mono text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full {{ $f->destino === 'VEHICULO' ? 'bg-accent' : ($f->destino === 'REPOSICION' ? 'bg-amber-500' : 'bg-slate-500') }}"></span>
                            FACTURA #{{ $f->numero_factura }}
                        </div>
                        <div class="font-black text-[0.95rem] leading-tight text-white mb-1 truncate">{{ $f->razon_social }}</div>
                        <div class="text-[0.6rem] text-muted-foreground font-bold uppercase tracking-widest">
                            @if($f->destino === 'VEHICULO') {{ $f->marca }} {{ $f->modelo }} @elseif($f->destino === 'REPOSICION') Reposición Stock @else {{ $f->cuenta_gasto ?? 'Gasto Local' }} @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-white/5">
                    <div>
                        <div class="text-[0.55rem] text-muted-foreground font-black uppercase tracking-widest mb-0.5">Egreso Confirmado</div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-[1.1rem] font-black text-red-500">$ {{ number_format($f->total_usd, 2, ',', '.') }}</span>
                            <span class="text-[0.6rem] font-bold text-muted-foreground uppercase">USD</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                        <a href="{{ route('facturas.show', $f->id) }}" class="p-2.5 rounded-xl bg-primary/10 text-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </a>
                        <button type="button"
                            onclick="abrirModalEliminar({{ $f->id }}, '{{ $f->numero_factura }}', '{{ addslashes($f->razon_social) }}', '{{ number_format($f->total_usd, 2, ',', '.') }}')"
                            class="p-2.5 rounded-xl bg-red-500/10 text-red-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-16 px-6 rounded-3xl border border-dashed border-white/10">
                <p class="font-black uppercase tracking-[0.2em] text-[0.6rem] text-muted-foreground/30">Sin Transacciones Recientes</p>
            </div>
        @endforelse
    </div>

    {{-- ── Modal de Confirmación de Eliminación ── --}}
    <div id="modal-eliminar" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="cerrarModalEliminar()"></div>
        <div class="relative w-full max-w-md bg-surface border border-white/10 rounded-3xl shadow-2xl overflow-hidden">
            {{-- Header --}}
            <div class="p-6 bg-red-500/10 border-b border-red-500/20">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-red-500/20 flex items-center justify-center text-red-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Eliminar Factura</h3>
                        <p class="text-[0.6rem] text-red-400 font-bold uppercase tracking-widest mt-0.5">Esta accion no se puede deshacer</p>
                    </div>
                </div>
            </div>
            {{-- Body --}}
            <div class="p-6 space-y-4">
                <div class="p-4 rounded-2xl bg-surface2 border border-white/5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest">Documento</span>
                        <span class="text-xs font-black text-white font-mono" id="del-numero"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest">Proveedor</span>
                        <span class="text-xs font-bold text-white" id="del-proveedor"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest">Monto USD</span>
                        <span class="text-xs font-black text-red-500" id="del-monto"></span>
                    </div>
                </div>
                <p class="text-[0.7rem] text-muted-foreground leading-relaxed">
                    Se eliminara la factura, el movimiento de caja asociado sera revertido y los gastos de vehiculo vinculados seran removidos.
                </p>
            </div>
            {{-- Footer --}}
            <div class="p-6 bg-surface3/30 border-t border-white/5 flex gap-3">
                <button type="button" onclick="cerrarModalEliminar()" class="btn btn-ghost flex-1 h-11 rounded-xl text-[0.65rem] font-black tracking-widest uppercase">Cancelar</button>
                <form id="form-eliminar" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn w-full h-11 rounded-xl text-[0.65rem] font-black tracking-widest uppercase bg-red-500 hover:bg-red-600 text-white transition-all shadow-lg shadow-red-500/25">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function abrirModalEliminar(id, numero, proveedor, monto) {
            document.getElementById('del-numero').textContent = '#' + numero;
            document.getElementById('del-proveedor').textContent = proveedor;
            document.getElementById('del-monto').textContent = '$ ' + monto;
            document.getElementById('form-eliminar').action = '{{ url("facturas") }}/' + id;
            document.getElementById('modal-eliminar').style.display = 'flex';
        }
        function cerrarModalEliminar() {
            document.getElementById('modal-eliminar').style.display = 'none';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarModalEliminar();
        });
    </script>
    @endpush
@endsection