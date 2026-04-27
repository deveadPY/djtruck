@extends('layouts.app')
@section('title', 'Vehículos')
@section('page-title', 'Inventario de Vehículos')

@section('content')
    {{-- ── Cabecera y Filtros ── --}}
    <div class="space-y-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-primary/10 text-primary md:hidden">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125v-10.5a1.125 1.125 0 0 0-1.125-1.125H3.375a1.125 1.125 0 0 0-1.125 1.125v4.5m17.25 2.25h-5.625c-.621 0-1.125.504-1.125 1.125v3.375m7.5-3.375h-7.5m7.5-1.125h-7.5" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Inventario Vehículos</h1>
                    <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold hidden md:block">Gestión de stock físico de camiones y maquinaria</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('vehicles.create') }}" class="btn btn-primary flex-1 md:flex-none py-3 px-6 rounded-xl shadow-lg shadow-primary/25 border-b-2 border-primary-hover active:translate-y-0.5 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="text-xs font-black uppercase tracking-wider">Nuevo Vehículo</span>
                </a>
            </div>
        </div>

        {{-- Buscador Estilizado --}}
        <form method="GET" action="{{ route('vehicles.index') }}" class="flex gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-muted-foreground/30">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Chasis, marca o modelo..." 
                    class="form-input !pl-11 !bg-surface/40 !backdrop-blur-md border-white/5 h-12 text-sm rounded-2xl focus:ring-primary/20 transition-all font-medium">
            </div>
            
            @if($q)
                <a href="{{ route('vehicles.index') }}" class="btn btn-ghost !bg-red-500/5 !text-red-400 border-red-500/10 px-4 rounded-2xl hover:!bg-red-500/20" title="Limpiar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </a>
            @endif
        </form>
    </div>

    {{-- Vista de Escritorio (Table Master) --}}
    <div class="hidden md:block erp-card !border-white/5">
        <div class="erp-card-header overflow-hidden !bg-surface3/10">
            <div class="flex items-center gap-3 w-full">
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground/80">Monitor de Inventario Físico</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-border/50 to-transparent"></div>
                <span class="px-2.5 py-1 rounded-lg bg-surface2 text-[0.6rem] font-black text-accent border border-white/5 uppercase tracking-tighter">
                    {{ $vehiculos->total() }} Unidades Disponibles
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="!pl-6">Identificación</th>
                        <th>Marca / Modelo</th>
                        <th>Año / Km</th>
                        <th>Costo Acumulado</th>
                        <th>Precio Venta</th>
                        <th>Estado</th>
                        <th class="text-right !pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehiculos as $v)
                        <tr class="hover:bg-primary/5 transition-all duration-200 group cursor-pointer" onclick="window.location='{{ route('vehicles.show', $v->id) }}'">
                            <td class="!pl-6">
                                <div class="font-mono text-[0.68rem] font-black text-accent bg-surface2 px-2 py-1 rounded border border-white/5 group-hover:border-accent/30 transition-colors w-fit">
                                    {{ $v->numero_chasis }}
                                </div>
                            </td>
                            <td>
                                <div class="font-black text-sm tracking-tight text-white group-hover:text-primary transition-colors">{{ $v->marca }}</div>
                                <div class="text-[0.65rem] text-muted-foreground uppercase font-bold tracking-widest mt-0.5">{{ $v->modelo }}</div>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-black text-xs">{{ $v->año }}</span>
                                    <span class="text-[0.65rem] text-muted-foreground font-mono">{{ number_format($v->kilometraje, 0, ',', '.') }} km</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-mono font-bold text-sm text-white">$ {{ number_format($v->costo_origen_usd + ($v->total_gastos_usd ?? 0), 2, ',', '.') }}</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <div class="w-1.5 h-1.5 rounded-full bg-surface2 border border-white/5"></div>
                                        <span class="text-[0.6rem] text-muted-foreground opacity-60 uppercase font-bold tracking-tighter">Base: $ {{ number_format($v->costo_origen_usd, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($v->precio_contado_usd || $v->precio_cuotas_usd)
                                    <div class="flex flex-col gap-1">
                                        @if($v->precio_contado_usd)
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[0.55rem] font-black text-emerald-500/70 uppercase tracking-tighter leading-none w-10">Cont.</span>
                                                <span class="font-black text-sm text-emerald-400">$ {{ number_format($v->precio_contado_usd, 2, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if($v->precio_cuotas_usd)
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[0.55rem] font-black text-indigo-400/70 uppercase tracking-tighter leading-none w-10">Cuot.</span>
                                                <span class="font-black text-sm text-indigo-400">$ {{ number_format($v->precio_cuotas_usd, 2, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($v->venta_precio_usd)
                                    <div class="flex flex-col">
                                        <span class="font-black text-sm text-primary group-hover:scale-110 origin-left transition-transform">$ {{ number_format($v->venta_precio_usd, 2, ',', '.') }}</span>
                                        @if($v->venta_moneda && $v->venta_moneda !== 'USD')
                                            <span class="text-[0.6rem] text-muted-foreground font-mono font-bold uppercase">{{ number_format($v->venta_precio_moneda ?? 0, 2, ',', '.') }} {{ $v->venta_moneda }}</span>
                                        @endif
                                    </div>
                                @elseif($v->precio_venta_sugerido_usd)
                                    <div class="flex flex-col">
                                        <span class="font-black text-sm text-amber-500">$ {{ number_format($v->precio_venta_sugerido_usd, 2, ',', '.') }}</span>
                                        <span class="text-[0.55rem] text-amber-500/70 uppercase font-black tracking-widest leading-none">SUGERIDO</span>
                                    </div>
                                @else
                                    <span class="text-[0.65rem] font-black text-muted-foreground/30 italic uppercase tracking-widest">Sin Precio</span>
                                @endif
                            </td>
                            <td>
                                @php $cls = match ($v->estado) { 'DISPONIBLE' => 'badge-disponible', 'EN_PREPARACION' => 'badge-preparacion', 'TOMA' => 'badge-toma', default => 'badge-vendido'}; @endphp
                                <span class="badge-status {{ $cls }} !text-[0.6rem] !px-2.5 !font-black uppercase tracking-tighter shadow-sm">{{ $v->estado }}</span>
                            </td>
                            <td class="text-right !pr-6" onclick="event.stopPropagation()">
                                <div class="flex justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('vehicles.show', $v->id) }}" class="p-2 rounded-xl hover:bg-accent/10 text-accent transition-all" title="Ver Detalle">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('vehicles.edit', $v->id) }}" class="p-2 rounded-xl hover:bg-primary/10 text-primary transition-all" title="Editar">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-20">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125v-10.5a1.125 1.125 0 0 0-1.125-1.125H3.375a1.125 1.125 0 0 0-1.125 1.125v4.5m17.25 2.25h-5.625c-.621 0-1.125.504-1.125 1.125v3.375m7.5-3.375h-7.5m7.5-1.125h-7.5" />
                                    </svg>
                                    <p class="font-black uppercase tracking-[0.2em] text-xs">Inventario Vacío</p>
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
        @forelse($vehiculos as $v)
            <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-surface2/40 backdrop-blur-md p-4 transition-all duration-300 active:scale-[0.98]" onclick="window.location='{{ route('vehicles.show', $v->id) }}'">
                @php $cls = match ($v->estado) { 'DISPONIBLE' => 'badge-disponible', 'EN_PREPARACION' => 'badge-preparacion', 'TOMA' => 'badge-toma', default => 'badge-vendido'}; @endphp
                <div class="absolute top-0 right-0 px-3 py-1 bg-surface2 rounded-bl-xl border-l border-b border-white/5">
                    <span class="badge-status {{ $cls }} !text-[0.55rem] !px-1.5 !py-0 !font-black uppercase tracking-tighter">{{ $v->estado }}</span>
                </div>

                <div class="flex items-start gap-3 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-surface/50 border border-white/5 flex flex-col items-center justify-center flex-shrink-0">
                        <span class="text-[0.5rem] text-muted-foreground font-black uppercase tracking-tighter">Año</span>
                        <span class="text-xs font-black text-accent">{{ $v->año }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-black text-[0.9rem] leading-tight text-white group-active:text-primary transition-colors truncate">{{ $v->marca }} {{ $v->modelo }}</div>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="px-2 py-0.5 rounded font-mono text-[0.65rem] font-black bg-surface text-accent/80 border border-white/10 uppercase tracking-tighter truncate max-w-[120px]">{{ $v->numero_chasis }}</span>
                            <span class="text-[0.6rem] text-muted-foreground font-bold uppercase tracking-widest whitespace-nowrap">{{ number_format($v->kilometraje, 0, ',', '.') }} km</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/5">
                    <div>
                        <div class="text-[0.55rem] text-muted-foreground font-black uppercase tracking-widest mb-1">Precio sugerido vta</div>
                        <div class="text-sm font-black text-accent-light leading-none">
                            @if($v->precio_contado_usd)
                                <div class="flex items-center gap-1">
                                    <span class="text-[0.5rem] text-emerald-500/70 font-black uppercase">C:</span>
                                    <span class="text-emerald-400">$ {{ number_format($v->precio_contado_usd, 2, ',', '.') }}</span>
                                </div>
                                @if($v->precio_cuotas_usd)
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="text-[0.5rem] text-indigo-400/70 font-black uppercase">Q:</span>
                                        <span class="text-indigo-400 text-xs">$ {{ number_format($v->precio_cuotas_usd, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                            @elseif($v->precio_cuotas_usd)
                                <span class="text-indigo-400">$ {{ number_format($v->precio_cuotas_usd, 2, ',', '.') }}</span>
                            @elseif($v->venta_precio_usd)
                                $ {{ number_format($v->venta_precio_usd, 2, ',', '.') }}
                            @elseif($v->precio_venta_sugerido_usd)
                                $ {{ number_format($v->precio_venta_sugerido_usd, 2, ',', '.') }}
                            @else
                                <span class="text-muted-foreground/30 italic text-[0.65rem] font-bold uppercase tracking-tighter">Consultar</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[0.55rem] text-muted-foreground font-black uppercase tracking-widest mb-1">Costo Acumulado</div>
                        <div class="text-[0.75rem] font-black text-white/80 leading-none">
                            $ {{ number_format($v->costo_origen_usd + ($v->total_gastos_usd ?? 0), 2, ',', '.') }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex gap-2" onclick="event.stopPropagation()">
                    <a href="{{ route('vehicles.edit', $v->id) }}" class="flex-1 h-11 flex items-center justify-center gap-2 rounded-xl bg-primary/10 text-primary text-[0.65rem] font-black uppercase border border-primary/20 tracking-[0.15em] transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        Editar Unid.
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-20 px-6 rounded-3xl border border-dashed border-white/10">
                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125v-10.5a1.125 1.125 0 0 0-1.125-1.125H3.375a1.125 1.125 0 0 0-1.125 1.125v4.5m17.25 2.25h-5.625c-.621 0-1.125.504-1.125 1.125v3.375m7.5-3.375h-7.5m7.5-1.125h-7.5" />
                    </svg>
                    <p class="font-black uppercase tracking-[0.2em] text-[0.6rem]">Stock de Vehículos Vacío</p>
                </div>
            </div>
        @endforelse
    </div>
        @if($vehiculos->hasPages())
            <div class="px-6 py-4 border-t border-border">
                {{ $vehiculos->links() }}
            </div>
        @endif
    </div>
@endsection
