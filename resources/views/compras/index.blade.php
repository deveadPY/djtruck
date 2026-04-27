@extends('layouts.app')
@section('title', 'Compras de Productos')
@section('page-title', 'Compras')

@section('content')
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
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight">Historial de Compras</h1>
                    <p class="text-[0.7rem] text-muted-foreground uppercase tracking-widest font-semibold hidden md:block">Registro de reposición de stock y gastos operativos</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @can('repuestos.crear')
                    <a href="{{ route('compras.create') }}" class="btn btn-primary flex-1 md:flex-none py-3 px-6 rounded-xl shadow-lg shadow-primary/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span class="text-xs font-black uppercase tracking-wider">Nueva Compra</span>
                    </a>
                @endcan
            </div>
        </div>

        {{-- Buscador Optimizado --}}
        <form method="GET" action="{{ route('compras.index') }}" class="flex gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-muted-foreground/40">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por factura o proveedor..." 
                    class="form-input !pl-10 !bg-surface/40 !backdrop-blur-md border-white/5 h-11 text-sm rounded-xl">
            </div>
            
            <button type="submit" class="btn btn-ghost !bg-primary/10 !text-primary border-primary/20 px-6 rounded-xl hover:!bg-primary/20">
                <span class="text-[0.7rem] font-bold uppercase">Filtrar</span>
            </button>
            
            @if($q)
                <a href="{{ route('compras.index') }}" class="btn btn-ghost !bg-red-500/5 !text-red-400 border-red-500/10 px-3 rounded-xl hover:!bg-red-500/20" title="Limpiar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </a>
            @endif
        </form>
    </div>

    {{-- Vista de Escritorio (Table) --}}
    <div class="hidden md:block erp-card">
        <div class="erp-card-header overflow-hidden">
            <div class="flex items-center gap-3">
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-muted-foreground/80">Listado Maestro de Compras</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-border/50 to-transparent"></div>
                <span class="px-2 py-0.5 rounded-full bg-surface2 text-[0.6rem] font-black text-muted-foreground border border-white/5 uppercase">
                    {{ $compras->total() }} Registros
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="!pl-6">Fecha</th>
                        <th>N° Factura</th>
                        <th>Proveedor</th>
                        <th>Estado / Moneda</th>
                        <th>Total (USD)</th>
                        <th class="text-right !pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($compras as $c)
                        <tr class="hover:bg-primary/5 transition-all duration-200 group">
                            <td class="!pl-6">
                                <span class="font-mono text-xs font-bold text-muted-foreground/80 group-hover:text-primary transition-colors">
                                    {{ \Carbon\Carbon::parse($c->fecha_compra)->format('d/m/Y') }}
                                </span>
                            </td>
                            <td>
                                <span class="px-2 py-1 rounded font-mono text-[0.7rem] font-bold bg-surface2 text-accent border border-white/5 uppercase">
                                    {{ $c->numero_factura ?? 'S/N' }}
                                </span>
                            </td>
                            <td>
                                <div class="font-bold text-sm tracking-tight group-hover:text-primary transition-colors">{{ $c->proveedor_nombre }}</div>
                                <div class="text-[0.6rem] text-muted-foreground uppercase font-bold tracking-widest mt-0.5">ID: #{{ $c->id }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="badge-status bg-surface2 text-muted-foreground text-[0.6rem] border-white/5">{{ $c->moneda_compra }}</span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                                </div>
                            </td>
                            <td>
                                <span class="font-mono font-black text-accent text-base group-hover:scale-110 origin-left transition-transform inline-block">
                                    $ {{ number_format($c->monto_total_usd, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-right !pr-6">
                                <div class="flex items-center justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('compras.show', $c->id) }}" class="p-2 rounded-xl hover:bg-primary/10 text-primary transition-all" title="Ver Detalle">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>

                                    @can('repuestos.eliminar')
                                        <div x-data="{ confirming: false }" class="inline-block">
                                            <button type="button" 
                                                @click="if(!confirming) { confirming = true; setTimeout(() => confirming = false, 3000) } else { $refs.deleteForm{{ $c->id }}.submit() }"
                                                class="p-2 rounded-xl transition-all duration-300 flex items-center gap-2 group overflow-hidden"
                                                :class="confirming ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'hover:bg-red-500/10 text-red-400 border border-transparent'">
                                                
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>

                                                <span x-show="confirming" x-transition class="text-[0.65rem] font-black uppercase tracking-tighter">Confirmar</span>
                                            </button>
                                            <form x-ref="deleteForm{{ $c->id }}" action="{{ route('compras.destroy', $c->id) }}" method="POST" class="hidden">
                                                @csrf @method('DELETE')
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-20 px-6">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <p class="font-black uppercase tracking-[0.2em] text-xs">Sin Compras Registradas</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vista de Móvil (Cards) --}}
    <div class="md:hidden space-y-4">
        @forelse($compras as $c)
            <div class="relative overflow-hidden rounded-2xl border border-white/5 bg-surface2/40 backdrop-blur-md p-4 transition-all duration-300 active:scale-[0.98]">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-surface/50 border border-white/5 flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-[0.5rem] text-muted-foreground font-black uppercase tracking-tighter">Fecha</span>
                            <span class="text-[0.65rem] font-black text-accent">{{ \Carbon\Carbon::parse($c->fecha_compra)->format('d/m') }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-black text-sm leading-tight text-white group-active:text-primary transition-colors truncate max-w-[180px]">{{ $c->proveedor_nombre }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-1.5 py-0.5 rounded font-mono text-[0.6rem] font-black bg-surface text-accent/80 border border-white/10 uppercase tracking-tighter">{{ $c->numero_factura ?? 'SIN FACTURA' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[0.6rem] text-muted-foreground font-bold uppercase tracking-widest">{{ $c->moneda_compra }}</div>
                        <div class="text-sm font-black text-accent-light mt-0.5">$ {{ number_format($c->monto_total_usd, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-4 border-t border-white/5">
                    <a href="{{ route('compras.show', $c->id) }}" class="flex-1 h-11 flex items-center justify-center gap-2 rounded-xl bg-primary/10 text-primary text-[0.65rem] font-black uppercase border border-primary/20 tracking-[0.15em] transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Ver Detalle
                    </a>

                    @can('repuestos.eliminar')
                        <form method="POST" action="{{ route('compras.destroy', $c->id) }}" class="contents" onsubmit="return confirm('¿Anular compra?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-11 h-11 flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 border border-red-500/20 active:bg-red-500/20 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            <div class="text-center py-20 px-6 rounded-3xl border border-dashed border-white/10">
                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <p class="font-black uppercase tracking-[0.2em] text-[0.6rem]">Sin registros de compras</p>
                </div>
            </div>
        @endforelse
    </div>
        @if($compras->hasPages())
            <div class="px-6 py-4 border-t border-border">
                {{ $compras->links() }}
            </div>
        @endif
    </div>
@endsection
