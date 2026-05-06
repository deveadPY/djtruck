@extends('layouts.app')
@section('title', 'Ventas')
@section('page-title', 'Ventas')

@section('content')
    @if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>@endif

    {{-- ── Cabecera y Filtros ── --}}
    <div class="space-y-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-primary/10 text-primary md:hidden">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Historial de Ventas</h1>
                    <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold hidden md:block">Registro global de transacciones y estados de facturación</p>
                </div>
            </div>

            @can('ventas.crear')
            <div class="flex items-center gap-2">
                <a href="{{ route('ventas.create') }}" class="btn btn-primary flex-1 md:flex-none py-3 px-6 rounded-xl shadow-lg shadow-primary/25 border-b-2 border-primary-hover active:translate-y-0.5 transition-all text-xs font-black uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nueva Venta
                </a>
            </div>
            @endcan
        </div>

        {{-- Filtros Avanzados Estilizados --}}
        <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 p-4">
            <form method="GET" action="{{ route('ventas.index') }}" id="ventasSearchForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Buscador Texto --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-muted-foreground/30">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Chasis, Cliente, N° Venta..." 
                        class="form-input !pl-11 !bg-surface underline-none h-11 text-xs rounded-xl border-white/5 focus:ring-primary/20 transition-all font-medium w-full">
                </div>

                {{-- Estado Select --}}
                <div class="relative">
                    <select name="estado" onchange="this.form.submit()" 
                        class="form-input !bg-surface h-11 text-xs rounded-xl border-white/5 focus:ring-primary/20 transition-all font-black uppercase tracking-widest w-full">
                        <option value="">TODOS LOS ESTADOS</option>
                        @foreach(['COMPLETADO','RESERVADO','EN_PROCESO','PRESUPUESTO','CANCELADO'] as $opt)
                            <option value="{{ $opt }}" @selected($estado === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rango de Fechas --}}
                <div class="flex gap-2 sm:col-span-2">
                    <div class="relative flex-1">
                        <input type="date" name="desde" value="{{ $desde }}" onchange="this.form.submit()"
                            class="form-input !bg-surface h-11 text-[0.65rem] rounded-xl border-white/5 focus:ring-primary/20 transition-all font-black uppercase tracking-tighter w-full">
                        <span class="absolute -top-2 left-3 px-1 bg-surface text-[0.5rem] font-black text-muted-foreground uppercase tracking-widest">Desde</span>
                    </div>
                    <div class="relative flex-1">
                        <input type="date" name="hasta" value="{{ $hasta }}" onchange="this.form.submit()"
                            class="form-input !bg-surface h-11 text-[0.65rem] rounded-xl border-white/5 focus:ring-primary/20 transition-all font-black uppercase tracking-tighter w-full">
                        <span class="absolute -top-2 left-3 px-1 bg-surface text-[0.5rem] font-black text-muted-foreground uppercase tracking-widest">Hasta</span>
                    </div>
                    @if($q || $estado || $desde || $hasta)
                        <a href="{{ route('ventas.index') }}" class="btn btn-ghost !bg-red-500/5 !text-red-400 border-red-500/10 px-4 rounded-xl hover:!bg-red-500/20" title="Limpiar">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Vista de Escritorio (Table Master) --}}
    <div class="hidden md:block erp-card !border-white/5">
        <div class="erp-card-header overflow-hidden !bg-surface3/10">
            <div class="flex items-center gap-3 w-full">
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground/80">Monitor de Transacciones</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-border/50 to-transparent"></div>
                <span class="px-2.5 py-1 rounded-lg bg-surface2 text-[0.6rem] font-black text-accent border border-white/5 uppercase tracking-tighter">
                    {{ $ventas->total() }} Operaciones
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="!pl-6">Operación</th>
                        <th>Fecha</th>
                        <th>Vehículo / Chasis</th>
                        <th>Cliente</th>
                        <th>Monto Venta</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right !pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $v)
                        <tr class="hover:bg-primary/5 transition-all duration-200 group cursor-pointer" onclick="window.location='{{ route('ventas.show', $v->id) }}'">
                            <td class="!pl-6">
                                <div class="font-mono text-[0.68rem] font-black text-accent bg-surface2 px-2 py-1 rounded border border-white/5 group-hover:border-accent/30 transition-all w-fit">
                                    #{{ $v->numero_venta }}
                                </div>
                            </td>
                            <td>
                                <div class="text-xs font-bold text-white/80">{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                @if($v->marca)
                                    <div class="font-black text-sm tracking-tight text-white group-hover:text-primary transition-colors">{{ $v->marca }} {{ $v->modelo }}</div>
                                    <div class="text-[0.6rem] text-muted-foreground font-mono font-bold uppercase tracking-widest mt-0.5">{{ $v->numero_chasis }}</div>
                                @else
                                    <div class="text-[0.65rem] text-muted-foreground uppercase font-black tracking-widest italic opacity-60">
                                        Multi-Items / Repuestos
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm font-bold text-white/90 truncate max-w-[180px]">{{ $v->cliente_nombre }}</div>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-black text-sm text-accent group-hover:scale-105 origin-left transition-transform">$ {{ number_format($v->precio_venta_usd, 2, ',', '.') }}</span>
                                    @if($v->moneda_venta !== 'USD')
                                        <span class="text-[0.6rem] text-muted-foreground font-mono font-black uppercase opacity-60">
                                            {{ number_format($v->precio_venta_moneda ?? 0, 2, ',', '.') }} {{ $v->moneda_venta }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @php $cls = match($v->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO'  => 'badge-vendido', 'RESERVADO'  => 'badge-preparacion', default => 'badge-toma' }; @endphp
                                <span class="badge-status {{ $cls }} !text-[0.6rem] !px-2.5 !font-black uppercase tracking-tighter shadow-sm">{{ $v->estado }}</span>
                            </td>
                            <td class="text-right !pr-6" onclick="event.stopPropagation()">
                                <a href="{{ route('ventas.show', $v->id) }}" class="p-2 rounded-xl hover:bg-accent/10 text-accent transition-all inline-flex items-center gap-2 group-hover:translate-x-1">
                                    <span class="text-[0.65rem] font-black uppercase tracking-widest hidden lg:inline">Detalles</span>
                                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-20">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <p class="font-black uppercase tracking-[0.2em] text-xs">Historial Vacío</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vista de Móvil (Grid Cards) --}}
    <div class="md:hidden space-y-4">
        @forelse($ventas as $v)
            <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-surface2/40 backdrop-blur-md p-5 transition-all duration-300 active:scale-[0.98]" onclick="window.location='{{ route('ventas.show', $v->id) }}'">
                @php $cls = match($v->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO'  => 'badge-vendido', 'RESERVADO'  => 'badge-preparacion', default => 'badge-toma' }; @endphp
                <div class="absolute top-0 right-0 px-3 py-1 bg-surface2 rounded-bl-xl border-l border-b border-white/5">
                    <span class="badge-status {{ $cls }} !text-[0.55rem] !px-1.5 !py-0 !font-black uppercase tracking-tighter">{{ $v->estado }}</span>
                </div>

                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/10 flex flex-col items-center justify-center text-accent flex-shrink-0 shadow-inner">
                        <span class="text-[0.45rem] font-black uppercase leading-none opacity-60">Día</span>
                        <span class="text-xs font-black">{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d') }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-mono text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                            OPERACIÓN #{{ $v->numero_venta }}
                        </div>
                        <div class="font-black text-[0.95rem] leading-tight text-white transition-colors truncate">
                            {{ $v->marca ? $v->marca.' '.$v->modelo : 'Venta de Repuestos' }}
                        </div>
                        <div class="text-[0.7rem] text-muted-foreground font-bold mt-1.5 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            {{ $v->cliente_nombre }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-white/5">
                    <div>
                        <div class="text-[0.55rem] text-muted-foreground font-black uppercase tracking-widest mb-0.5">Monto Total Recibido</div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-[1.1rem] font-black text-accent">$ {{ number_format($v->precio_venta_usd, 2, ',', '.') }}</span>
                            <span class="text-[0.6rem] font-bold text-muted-foreground uppercase">USD</span>
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-surface2 border border-white/5 flex items-center justify-center text-muted-foreground/30">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-20 px-6 rounded-3xl border border-dashed border-white/10">
                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <p class="font-black uppercase tracking-[0.2em] text-[0.6rem]">Sin Historial Registrado</p>
                </div>
            </div>
        @endforelse
    </div>

        {{-- ── Paginación ──────────────────────────────────────────────────── --}}
        @if($ventas->hasPages())
            <div class="px-4 py-3 border-t" style="border-color:var(--border)">
                {{ $ventas->links() }}
            </div>
        @endif
    </div>

    {{-- ── Atajo: Enter en el input envía el form ─────────────────────────── --}}
    <script>
        document.getElementById('ventasQ').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('ventasSearchForm').submit(); }
        });
    </script>
@endsection
