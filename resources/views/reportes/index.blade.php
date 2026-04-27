@extends('layouts.app')
@section('title', 'Reportes')
@section('page-title', 'Reportes & Análisis')

@section('content')
    {{-- ── Cabecera y Resumen Ejecutivo ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1 md:px-0">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Centro de Inteligencia</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">Análisis predictivo y rendimiento operativo en tiempo real</p>
        </div>

        <div class="flex items-center gap-3 overflow-x-auto pb-4 md:pb-0 scrollbar-hide">
            <div class="flex items-center gap-2 p-1 bg-surface2/40 backdrop-blur-md rounded-2xl border border-white/5 shadow-xl">
                <a href="{{ route('reportes.export', ['tipo'=>'ventas','desde'=>$desde,'hasta'=>$hasta]) }}" class="px-3 py-2 rounded-xl text-[0.6rem] font-black text-muted-foreground hover:text-primary hover:bg-white/5 transition-all uppercase tracking-widest whitespace-nowrap flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Ventas
                </a>
                <a href="{{ route('reportes.export', ['tipo'=>'stock','desde'=>$desde,'hasta'=>$hasta]) }}" class="px-3 py-2 rounded-xl text-[0.6rem] font-black text-muted-foreground hover:text-accent hover:bg-white/5 transition-all uppercase tracking-widest whitespace-nowrap flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m16.5 0-1.25-3.75a2.25 2.25 0 0 0-2.134-1.5H7.134a2.25 2.25 0 0 0-2.134 1.5L3.75 7.5m16.5 0h-16.5" />
                    </svg>
                    Stock
                </a>
                <a href="{{ route('reportes.export', ['tipo'=>'mora','desde'=>$desde,'hasta'=>$hasta]) }}" class="px-3 py-2 rounded-xl text-[0.6rem] font-black text-muted-foreground hover:text-red-500 hover:bg-white/5 transition-all uppercase tracking-widest whitespace-nowrap flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    Mora
                </a>
            </div>
        </div>
    </div>

    {{-- ── Filtros y Control de Tiempo ── --}}
    <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 mb-8 overflow-hidden shadow-2xl relative">
        <div class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent pointer-events-none"></div>
        <form method="GET" action="{{ route('reportes.index') }}" class="erp-card-body p-4 md:p-6 flex flex-col lg:flex-row items-stretch lg:items-end gap-6 relative z-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                <div class="space-y-2">
                    <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Génesis del Periodo</label>
                    <input type="date" name="desde" value="{{ $desde }}" 
                        class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase text-center w-full" 
                        style="color-scheme: dark">
                </div>
                <div class="space-y-2">
                    <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Límite Temporal</label>
                    <input type="date" name="hasta" value="{{ $hasta }}" 
                        class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase text-center w-full" 
                        style="color-scheme: dark">
                </div>
            </div>
            <button type="submit" class="h-12 px-6 lg:px-10 bg-white/5 hover:bg-white/10 text-white rounded-xl border border-white/10 text-[0.65rem] font-black uppercase tracking-[0.2em] transition-all active:scale-95 flex items-center justify-center gap-3">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span class="whitespace-nowrap">Procesar Metas</span>
            </button>
        </form>
    </div>

    {{-- ── KPIs de Alto Performance ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- KPI 1: Operaciones --}}
        <div class="relative group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-primary to-accent rounded-3xl blur opacity-10 group-hover:opacity-20 transition duration-500"></div>
            <div class="stat-card !h-32 !bg-surface2/40 !backdrop-blur-md !border-white/5 flex flex-col justify-between p-5 relative">
                <div class="flex items-center justify-between">
                    <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest">Contratos Cerrados</span>
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black text-white italic tracking-tight">{{ $resumenVentas->total_ventas ?? 0 }}</div>
                    <div class="text-[0.5rem] mt-1 font-bold text-muted-foreground uppercase tracking-[0.2em]">Cierres verificados</div>
                </div>
            </div>
        </div>

        {{-- KPI 2: Facturación --}}
        <div class="relative group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-accent to-primary rounded-3xl blur opacity-10 group-hover:opacity-20 transition duration-500"></div>
            <div class="stat-card !h-32 !bg-surface2/40 !backdrop-blur-md !border-white/5 flex flex-col justify-between p-5 relative">
                <div class="flex items-center justify-between">
                    <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest">Facturación Bruta</span>
                    <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black text-white italic tracking-tight">$ {{ number_format($resumenVentas->total_usd ?? 0, 0, ',', '.') }}</div>
                    <div class="text-[0.5rem] mt-1 font-bold text-muted-foreground uppercase tracking-[0.2em]">Acumulado USD</div>
                </div>
            </div>
        </div>

        {{-- KPI 3: Margen --}}
        <div class="relative group">
            <div class="stat-card !h-32 !bg-surface2/40 !backdrop-blur-md !border-white/5 flex flex-col justify-between p-5">
                <div class="flex items-center justify-between">
                    <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest">Rentabilidad</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xs font-black text-primary italic">{{ number_format($resumenVentas->margen_promedio_pct ?? 0, 1) }}%</span>
                        <div class="w-2 h-2 rounded-full bg-primary/40 animate-pulse"></div>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black text-primary italic tracking-tight">$ {{ number_format($resumenVentas->margen_total_usd ?? 0, 0, ',', '.') }}</div>
                    <div class="text-[0.5rem] mt-1 font-bold text-muted-foreground uppercase tracking-[0.2em]">Margen total realizado</div>
                </div>
            </div>
        </div>

        {{-- KPI 4: Mora --}}
        <div class="relative group">
            <div class="stat-card !h-32 !bg-surface2/40 !backdrop-blur-md !border-white/5 flex flex-col justify-between p-5 relative overflow-hidden group">
                <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-red-500 to-transparent opacity-50"></div>
                <div class="flex items-center justify-between">
                    <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest">Cartera en Riesgo</span>
                    <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center text-red-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-black text-red-500 italic tracking-tight">$ {{ number_format($cuotasMora->sum('monto_vencido_usd'), 0, ',', '.') }}</div>
                    <div class="text-[0.5rem] mt-1 font-bold text-muted-foreground uppercase tracking-[0.2em]">{{ $cuotasMora->count() }} Casos críticos</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Visualización de Datos (Gráficos y Top) ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Gráfico Evolución --}}
        <div class="lg:col-span-2 erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 shadow-2xl overflow-hidden relative group">
            <div class="erp-card-header !bg-transparent border-b border-white/5 py-4">
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic px-2">Flujo Comercial Multianual</h2>
            </div>
            <div class="erp-card-body p-2 md:p-6">
                <div class="h-[250px] md:h-[300px] relative">
                    <canvas id="chartVentasMes"></canvas>
                </div>
            </div>
        </div>

        {{-- Liderazgo Comercial (Top Clientes) --}}
        <div class="erp-card !bg-surface/20 !backdrop-blur-md !border-white/5 shadow-2xl">
            <div class="erp-card-header !bg-transparent border-b border-white/5 py-4 flex items-center justify-between px-4">
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic">Top Performance</h2>
                <div class="w-6 h-6 rounded bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </div>
            </div>
            <div class="erp-card-body p-2 scrollbar-hide overflow-y-auto max-h-[350px]">
                @foreach($topClientes as $i => $c)
                    <div class="p-3 mb-1 rounded-2xl hover:bg-white/5 border border-transparent hover:border-white/5 transition-all group flex items-center justify-between gap-4 cursor-default">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-[0.6rem] font-black text-primary/40 group-hover:text-primary transition-colors italic w-4 text-center">0{{ $i+1 }}</span>
                            <div class="flex flex-col min-w-0">
                                <span class="text-[0.7rem] font-black text-white italic truncate uppercase">{{ $c->razon_social }}</span>
                                <span class="text-[0.5rem] font-bold text-muted-foreground uppercase tracking-widest">{{ $c->total_ventas }} Cierres</span>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-[0.7rem] font-black text-accent italic">$ {{ number_format($c->total_comprado_usd, 0, ',', '.') }}</div>
                            <div class="w-20 h-1 bg-surface3 rounded-full overflow-hidden mt-1 opacity-20">
                                <div class="h-full bg-accent" style="width:{{ ($c->total_comprado_usd / ($topClientes[0]->total_comprado_usd ?: 1)) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Auditoría Stock y Mora ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Valuación de Activos --}}
        <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5">
            <div class="erp-card-header !bg-transparent border-b border-white/5 flex items-center justify-between px-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic">Inventario de Capital</h2>
                </div>
                <span class="text-xs font-black text-accent italic">$ {{ number_format($totalStockUsd, 0, ',', '.') }}</span>
            </div>
            <div class="overflow-x-auto hidden md:block">
                <table class="erp-table text-xs">
                    <thead>
                        <tr class="text-[0.6rem] uppercase tracking-widest">
                            <th class="!pl-6">Estado Activo</th>
                            <th class="text-center">Q</th>
                            <th class="text-right">Valor Libro USD</th>
                            <th class="text-right !pr-6">Precio Sugerido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockValuacion as $s)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="!pl-6">
                                    <span class="badge-status {{ $s->estado === 'DISPONIBLE' ? 'badge-disponible' : 'badge-preparacion' }} !text-[0.55rem] font-black uppercase tracking-widest">
                                        {{ $s->estado }}
                                    </span>
                                </td>
                                <td class="text-center font-black text-white italic">{{ $s->cantidad }}</td>
                                <td class="text-right text-white/70 font-mono italic">$ {{ number_format($s->valor_libro_usd, 0, ',', '.') }}</td>
                                <td class="text-right !pr-6 text-accent font-black italic">$ {{ number_format($s->precio_sugerido_usd ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Mobile Stock List --}}
            <div class="md:hidden p-4 space-y-3">
                @foreach($stockValuacion as $s)
                    <div class="p-4 rounded-xl bg-surface2/30 border border-white/5 flex items-center justify-between group">
                        <div class="flex flex-col gap-1">
                            <span class="text-[0.5rem] font-black text-muted-foreground uppercase opacity-50 tracking-widest">{{ $s->estado }}</span>
                            <span class="text-lg font-black text-white italic tracking-tighter">{{ $s->cantidad }} <small class="text-[0.6rem] opacity-50 not-italic uppercase font-bold">Unidades</small></span>
                        </div>
                        <div class="text-right">
                            <div class="text-[0.8rem] font-black text-accent italic">$ {{ number_format($s->precio_sugerido_usd ?? 0, 0, ',', '.') }}</div>
                            <div class="text-[0.5rem] font-bold text-muted-foreground uppercase tracking-widest opacity-40">Libro: $ {{ number_format($s->valor_libro_usd, 0, ',', '.') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Clientes en Mora --}}
        <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5">
            <div class="erp-card-header !bg-transparent border-b border-white/5 flex items-center justify-between px-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic">Alerta de Recaudación</h2>
                    <div class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></div>
                </div>
                <span class="text-xs font-black text-red-500 italic">$ {{ number_format($cuotasMora->sum('monto_vencido_usd'), 0, ',', '.') }}</span>
            </div>
            <div class="overflow-x-auto hidden md:block">
                <table class="erp-table text-xs">
                    <thead>
                        <tr class="text-[0.6rem] uppercase tracking-widest">
                            <th class="!pl-6">Identidad</th>
                            <th class="text-center">Cuotas</th>
                            <th class="text-right">Exposición USD</th>
                            <th class="text-center !pr-6">Máx. Retraso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cuotasMora as $m)
                            <tr class="hover:bg-red-500/5 transition-colors group cursor-pointer" onclick="window.location='{{ route('clientes.show', $m->cliente_id) }}'">
                                <td class="!pl-6">
                                    <span class="font-black text-white/80 group-hover:text-white uppercase italic truncate max-w-[150px] inline-block">{{ $m->cliente }}</span>
                                </td>
                                <td class="text-center font-black text-white italic">{{ $m->cuotas_vencidas }}</td>
                                <td class="text-right text-red-500 font-black italic">$ {{ number_format($m->monto_vencido_usd, 0, ',', '.') }}</td>
                                <td class="text-center !pr-6">
                                    <span class="badge-status {{ $m->max_dias_mora > 60 ? 'badge-vendido' : 'badge-preparacion' }} !text-[0.55rem] font-black">
                                        {{ $m->max_dias_mora }} DÍAS
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-16 text-muted-foreground/30 font-black uppercase tracking-[0.2em] text-xs">Zona Libre de Mora</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Mobile Mora List --}}
            <div class="md:hidden p-4 space-y-3">
                @forelse($cuotasMora as $m)
                    <div class="p-4 rounded-xl bg-red-500/5 border border-red-500/10 space-y-3 shadow-lg" onclick="window.location='{{ route('clientes.show', $m->cliente_id) }}'">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.7rem] font-black text-white italic uppercase truncate w-2/3 tracking-tighter">{{ $m->cliente }}</span>
                            <span class="badge-status {{ $m->max_dias_mora > 60 ? 'badge-vendido' : 'badge-preparacion' }} !text-[0.5rem]">{{ $m->max_dias_mora }}D MORA</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-red-500/10">
                            <div class="flex flex-col">
                                <span class="text-[0.5rem] font-bold text-muted-foreground uppercase opacity-40">{{ $m->cuotas_vencidas }} CUOTAS</span>
                                <span class="text-[0.9rem] font-black text-red-500 italic tracking-tighter">$ {{ number_format($m->monto_vencido_usd, 0, ',', '.') }}</span>
                            </div>
                            <svg class="w-4 h-4 text-red-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 opacity-20 font-black uppercase text-[0.6rem] tracking-widest text-red-500">Cartera Saneada</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Registro Detallado de Margen ── --}}
    <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 shadow-2xl relative overflow-hidden">
        <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-accent/5 rounded-full blur-3xl"></div>
        <div class="erp-card-header !bg-transparent border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4 px-4 py-4">
            <div class="flex flex-col">
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic leading-tight">Métricas de Rentabilidad Unitaria</h2>
                <span class="text-[0.5rem] font-bold text-muted-foreground/40 uppercase tracking-widest italic">Auditoría Global de Operaciones</span>
            </div>
            <a href="{{ route('reportes.export', ['tipo'=>'rentabilidad','desde'=>$desde,'hasta'=>$hasta]) }}" class="btn btn-ghost !text-[0.55rem] !font-black !uppercase !tracking-widest !px-4 border border-white/5 h-10 w-full md:w-auto flex items-center justify-center">↓ EXPORTAR DATA</a>
        </div>
        
        <div class="overflow-x-auto hidden md:block">
            <table class="erp-table text-xs">
                <thead>
                    <tr class="text-[0.6rem] uppercase tracking-widest text-muted-foreground/60 border-b border-white/5">
                        <th class="!pl-6">Cierre</th>
                        <th>Aliado Comercial</th>
                        <th>Activo Vinculado</th>
                        <th class="text-right">Valuación Libro</th>
                        <th class="text-right">Precio Cierre</th>
                        <th class="text-right">Retorno USD</th>
                        <th class="text-center !pr-6">ROI %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rentabilidad as $r)
                        <tr class="hover:bg-primary/5 group transition-all duration-200 cursor-pointer" onclick="window.location='{{ route('ventas.show', $r->id) }}'">
                            <td class="!pl-6">
                                <div class="flex flex-col min-w-[100px]">
                                    <span class="text-[0.65rem] font-black text-white italic uppercase">{{ $r->numero_venta }}</span>
                                    <span class="text-[0.55rem] text-muted-foreground font-bold">{{ \Carbon\Carbon::parse($r->fecha_venta)->format('d.m.Y') }}</span>
                                </div>
                            </td>
                            <td><span class="font-black text-white/70 uppercase text-[0.65rem] truncate max-w-[120px] inline-block italic tracking-tighter">{{ $r->cliente }}</span></td>
                            <td><span class="font-bold text-muted-foreground text-[0.65rem] uppercase italic tracking-tighter">{{ $r->marca }} {{ $r->modelo }}</span></td>
                            <td class="text-right font-mono text-white/50">$ {{ number_format($r->valor_libro_snapshot, 0, ',', '.') }}</td>
                            <td class="text-right font-black text-white italic tracking-tighter">$ {{ number_format($r->precio_venta_usd, 0, ',', '.') }}</td>
                            <td class="text-right">
                                <span class="font-black {{ ($r->margen_bruto_usd ?? 0) >= 0 ? 'text-primary' : 'text-red-500' }} italic">
                                    $ {{ number_format($r->margen_bruto_usd ?? 0, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center !pr-6">
                                @php $pct = round($r->margen_pct ?? 0, 1); @endphp
                                <span class="badge-status {{ $pct >= 15 ? 'badge-disponible' : ($pct >= 8 ? 'badge-preparacion' : 'badge-vendido') }} !text-[0.5rem] !font-black !px-2 uppercase">
                                    {{ $pct }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-20 text-muted-foreground/20 font-black uppercase tracking-[0.5em] text-xs">Sin transacciones registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Mobile Rentabilidad List --}}
        <div class="md:hidden p-4 space-y-4">
            @forelse($rentabilidad as $r)
                <div class="p-4 rounded-xl bg-surface2/30 border border-white/5 space-y-4 shadow-xl" onclick="window.location='{{ route('ventas.show', $r->id) }}'">
                    <div class="flex items-center justify-between border-b border-white/5 pb-2">
                        <div class="flex flex-col">
                            <span class="text-[0.65rem] font-black text-primary italic uppercase tracking-widest">{{ $r->numero_venta }}</span>
                            <span class="text-[0.5rem] text-muted-foreground font-bold">{{ \Carbon\Carbon::parse($r->fecha_venta)->format('d.m.Y') }}</span>
                        </div>
                        @php $pct = round($r->margen_pct ?? 0, 1); @endphp
                        <span class="badge-status {{ $pct >= 15 ? 'badge-disponible' : ($pct >= 8 ? 'badge-preparacion' : 'badge-vendido') }} !text-[0.5rem] !font-black !px-2 italic">{{ $pct }}% ROI</span>
                    </div>
                    <div class="space-y-1">
                        <span class="block text-[0.7rem] font-black text-white uppercase italic truncate tracking-tighter">{{ $r->cliente }}</span>
                        <span class="block text-[0.6rem] font-bold text-muted-foreground uppercase italic tracking-tighter opacity-60">{{ $r->marca }} {{ $r->modelo }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-white/5">
                        <div class="flex flex-col">
                            <span class="text-[0.5rem] font-black text-muted-foreground uppercase opacity-40">Precio Cierre</span>
                            <span class="text-[0.85rem] font-black text-white italic tracking-tighter">$ {{ number_format($r->precio_venta_usd, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="text-[0.5rem] font-black text-muted-foreground uppercase opacity-40">Retorno Neto</span>
                            <span class="text-[0.85rem] font-black {{ ($r->margen_bruto_usd ?? 0) >= 0 ? 'text-primary' : 'text-red-500' }} italic tracking-tighter">$ {{ number_format($r->margen_bruto_usd ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 opacity-20 font-black uppercase text-[0.6rem] tracking-widest italic tracking-[0.5em]">Sin Registros Corrientes</div>
            @endforelse
        </div>

        @if($rentabilidad->hasPages())
            <div class="px-6 py-4 border-t border-white/5 overflow-x-auto scrollbar-hide">{{ $rentabilidad->links() }}</div>
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const raw = @json($ventasPorMes);
    const labels  = raw.map(function(m) {
        const [y, mo] = m.mes.split('-');
        return new Date(y, mo - 1).toLocaleDateString('es-PY', { month: 'short', year: '2-digit' }).toUpperCase();
    });
    const totales  = raw.map(m => parseFloat(m.total_usd) || 0);
    const margenes = raw.map(m => parseFloat(m.margen_usd) || 0);

    const isDark = true;
    const gridColor = 'rgba(255,255,255,0.03)';
    const textColor = 'rgba(255,255,255,0.4)';

    new Chart(document.getElementById('chartVentasMes'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'FACTURACIÓN',
                    data: totales,
                    borderColor: '#6c63ff',
                    backgroundColor: 'rgba(108,99,255,0.05)',
                    borderWidth: 4,
                    pointBackgroundColor: '#6c63ff',
                    pointBorderColor: 'rgba(255,255,255,0.2)',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'RENTABILIDAD',
                    data: margenes,
                    borderColor: '#00d4aa',
                    backgroundColor: 'rgba(0,212,170,0.05)',
                    borderWidth: 4,
                    pointBackgroundColor: '#00d4aa',
                    pointBorderColor: 'rgba(255,255,255,0.2)',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { color: textColor, font: { family: 'Inter', size: 9, weight: '900' }, boxWidth: 10, usePointStyle: true, padding: 20 } 
                },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.9)',
                    titleFont: { size: 10, weight: 'bold' },
                    bodyFont: { size: 12, weight: 'black' },
                    padding: 12,
                    displayColors: true,
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    callbacks: {
                        label: ctx => '   $ ' + ctx.parsed.y.toLocaleString('de-DE', { minimumFractionDigits: 0 }) + ' USD'
                    }
                }
            },
            scales: {
                x: { 
                    ticks: { color: textColor, font: { size: 8, weight: '800' } }, 
                    grid: { display: false } 
                },
                y: { 
                    beginAtZero: true,
                    ticks: { 
                        color: textColor, 
                        font: { size: 8, weight: '800' }, 
                        callback: v => '$' + (v >= 1000 ? (v/1000).toFixed(0) + 'K' : v) 
                    }, 
                    grid: { color: gridColor } 
                },
            }
        }
    });
})();
</script>
@endpush
