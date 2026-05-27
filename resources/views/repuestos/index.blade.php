@extends('layouts.app')
@section('title', 'Productos')
@section('page-title', 'Productos / Stock')

@section('content')
    {{-- ── Cabecera y Filtros ── --}}
    <div class="space-y-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-primary/10 text-primary md:hidden">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight">Inventario de Productos</h1>
                    <p class="text-[0.7rem] text-muted-foreground uppercase tracking-widest font-semibold hidden md:block">Gestión de repuestos y mercadería</p>
                </div>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 no-scrollbar">
                @can('repuestos.crear')
                    <label class="btn btn-ghost !bg-surface2/50 !backdrop-blur-sm border-white/5 cursor-pointer flex-shrink-0" title="Importar">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                        </svg>
                        <span class="text-xs font-semibold">Importar</span>
                        <form id="importForm" method="POST" action="{{ route('repuestos.import') }}" enctype="multipart/form-data" style="display:none">
                            @csrf
                            <input id="archivoInput" type="file" name="archivo" accept=".xlsx,.xls,.csv" onchange="document.getElementById('importForm').submit()">
                        </form>
                    </label>
                @endcan

                <a href="{{ route('repuestos.export') }}" class="btn btn-ghost !bg-surface2/50 !backdrop-blur-sm border-white/5 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <span class="text-xs font-semibold">Exportar</span>
                </a>

                @can('repuestos.crear')
                    <a href="{{ route('repuestos.create') }}" class="btn btn-primary flex-shrink-0 shadow-lg shadow-primary/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider">Nuevo</span>
                    </a>
                @endcan
            </div>
        </div>

        {{-- Buscador y Filtros Optimizados --}}
        <form method="GET" action="{{ route('repuestos.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-2">
            <div class="md:col-span-2 relative">
                <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-muted-foreground/50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Código, nombre o marca..." 
                    class="form-input !pl-10 !bg-surface/40 !backdrop-blur-md border-white/5 h-11 text-sm rounded-xl">
            </div>
            
            <div class="flex gap-2">
                <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2.5 rounded-xl border border-white/5 bg-surface/40 backdrop-blur-md hover:bg-surface3 transition-all">
                    <input type="checkbox" name="stock_bajo" value="1" @checked($stockBajo) onchange="this.form.submit()" class="rounded-md border-white/10 bg-surface3 text-primary focus:ring-primary/30">
                    <span class="text-xs font-bold uppercase tracking-tighter {{ $stockBajo ? 'text-amber-500' : 'text-muted-foreground' }}">⚠️ S. Bajo</span>
                </label>

                <button type="submit" class="btn btn-ghost !bg-primary/10 !text-primary border-primary/20 px-6 rounded-xl hover:!bg-primary/20">
                    <span class="text-xs font-bold uppercase">Filtrar</span>
                </button>
                
                @if($q || $stockBajo)
                    <a href="{{ route('repuestos.index') }}" class="btn btn-ghost !bg-red-500/5 !text-red-400 border-red-500/10 px-3 rounded-xl hover:!bg-red-500/20" title="Limpiar">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($stockBajo)
        <div class="flash-error flex items-center gap-2 py-2.5 px-4 text-[0.65rem] font-bold uppercase tracking-wider mb-4 border-amber-500/20 bg-amber-500/5 text-amber-500 rounded-xl">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            Filtrando productos con Stock Crítico
        </div>
    @endif

    {{-- Vista de escritorio (Table) --}}
    <div class="hidden md:block erp-card">
        <div class="erp-card-header overflow-hidden">
            <div class="flex items-center gap-3">
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-muted-foreground/80">Listado Maestro de Productos</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-border/50 to-transparent"></div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="!pl-6">Código</th>
                        <th>Descripción / Marca</th>
                        <th>Proveedor</th>
                        <th>Stock Actual</th>
                        <th>P. Venta</th>
                        <th class="text-right !pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repuestos as $r)
                        @php $bajominimo = ($r->stock_minimo > 0 && $r->stock_actual <= $r->stock_minimo); @endphp
                        <tr class="hover:bg-primary/5 transition-all duration-200 group">
                            <td class="!pl-6">
                                <span class="px-2.5 py-1 rounded font-mono text-xs font-bold bg-surface2 text-accent border border-white/5 group-hover:border-accent/30 transition-colors">{{ $r->codigo }}</span>
                            </td>
                            <td>
                                <div class="font-bold text-sm tracking-tight group-hover:text-primary transition-colors">{{ $r->descripcion }}</div>
                                <div class="text-[0.65rem] text-muted-foreground uppercase font-bold tracking-widest mt-0.5">{{ $r->marca_compatible ?? 'Genérico' }} — {{ $r->unidad_medida }}</div>
                            </td>
                            <td>
                                <div class="text-[0.68rem] text-muted-foreground font-semibold uppercase truncate max-w-[150px]" title="{{ $r->proveedor_nombre }}">
                                    {{ $r->proveedor_nombre ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2 font-mono text-base font-black {{ $bajominimo ? 'text-amber-500' : 'text-accent' }}">
                                    {{ number_format($r->stock_actual, 0, ',', '.') }}
                                    @if($bajominimo)
                                        <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                                    @endif
                                </div>
                                <div class="text-[0.6rem] text-muted-foreground uppercase font-bold">Mín: {{ number_format($r->stock_minimo, 0) }}</div>
                            </td>
                            <td>
                                @if($r->precio_venta_usd)
                                    <span class="font-mono font-bold text-accent text-sm">$ {{ number_format($r->precio_venta_usd, 2, ',', '.') }}</span>
                                @else
                                    <span class="text-[0.65rem] font-bold text-muted-foreground/30 italic">SIN PRECIO</span>
                                @endif
                            </td>
                            <td class="text-right !pr-6">
                                <div class="flex justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                    @can('repuestos.editar')
                                        <a href="{{ route('repuestos.edit', $r->id) }}" class="p-2 rounded-xl hover:bg-primary/10 text-primary transition-all" title="Editar">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </a>

                                        {{-- Descontinuar / Reactivar --}}
                                        <form method="POST"
                                              action="{{ route('repuestos.toggleActive', $r->id) }}"
                                              class="inline"
                                              data-danger-confirm="{{ $r->activo ? 'Ya no aparecerá en alertas de stock ni en selectores de venta. Esta acción es reversible.' : 'El producto volverá a estar disponible en ventas y alertas.' }}"
                                              data-danger-title="{{ $r->activo ? 'Descontinuar ' . $r->codigo . '?' : 'Reactivar ' . $r->codigo . '?' }}"
                                              data-danger-action-label="{{ $r->activo ? 'Descontinuar' : 'Reactivar' }}"
                                              data-danger-icon="{{ $r->activo ? 'stop' : 'warning' }}">
                                            @csrf
                                            <input type="hidden" name="discontinuar" value="{{ $r->activo ? 1 : 0 }}">
                                            <button class="p-2 rounded-xl transition-all {{ $r->activo ? 'hover:bg-amber-500/10 text-amber-500' : 'hover:bg-green-500/10 text-green-500' }}"
                                                    title="{{ $r->activo ? 'Descontinuar (ya no se vende)' : 'Reactivar' }}">
                                                @if($r->activo)
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>
                                    @endcan
                                    @can('repuestos.eliminar')
                                        <form method="POST"
                                              action="{{ route('repuestos.destroy', $r->id) }}"
                                              class="inline"
                                              data-danger-confirm="Esta acción eliminará el producto {{ $r->codigo }} ({{ \Illuminate\Support\Str::limit($r->descripcion, 50) }}) del inventario. Se conservará el historial de ventas previas."
                                              data-danger-title="Eliminar producto"
                                              data-danger-action-label="Sí, eliminar"
                                              data-danger-icon="trash">
                                            @csrf @method('DELETE')
                                            <button class="p-2 rounded-xl hover:bg-red-500/10 text-red-500 transition-all" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-20">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25-3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                    </svg>
                                    <p class="font-bold uppercase tracking-[0.2em] text-xs">Sin registros</p>
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
        @forelse($repuestos as $r)
            @php $bajominimo = ($r->stock_minimo > 0 && $r->stock_actual <= $r->stock_minimo); @endphp
            <div class="relative overflow-hidden rounded-2xl border border-white/5 bg-surface2/40 backdrop-blur-md p-4 transition-all duration-300">
                @if($bajominimo)
                    <div class="absolute top-0 right-0 px-3 py-1 bg-amber-500/10 text-amber-500 text-[0.6rem] font-black uppercase tracking-widest rounded-bl-xl border-b border-l border-amber-500/20">
                        Stock Bajo
                    </div>
                @endif
                
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-surface/50 border border-white/5 flex flex-col items-center justify-center flex-shrink-0">
                        <span class="text-[0.6rem] text-muted-foreground font-black uppercase tracking-tighter">Stock</span>
                        <span class="text-xs font-black {{ $bajominimo ? 'text-amber-500' : 'text-accent' }}">{{ number_format($r->stock_actual, 0) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-sm leading-tight text-white group-active:text-primary transition-colors">{{ $r->descripcion }}</div>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="px-1.5 py-0.5 rounded font-mono text-[0.55rem] font-black bg-surface text-accent/80 border border-white/10 uppercase tracking-tighter">{{ $r->codigo }}</span>
                            <span class="text-[0.6rem] text-muted-foreground font-bold uppercase tracking-[0.1em]">{{ $r->marca_compatible ?? 'Genérico' }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/5">
                    <div>
                        <div class="text-[0.55rem] text-muted-foreground font-black uppercase tracking-widest mb-1">Precio Venta</div>
                        <div class="text-sm font-black text-accent-light">
                            @if($r->precio_venta_usd)
                                $ {{ number_format($r->precio_venta_usd, 2, ',', '.') }}
                                <span class="text-[0.6rem] opacity-50 ml-0.5 font-bold">USD</span>
                            @else
                                <span class="text-muted-foreground/30 italic text-[0.65rem] font-bold">Sin precio</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[0.55rem] text-muted-foreground font-black uppercase tracking-widest mb-1">Unidad / Mínimo</div>
                        <div class="text-[0.7rem] font-bold text-muted-foreground/80">
                            {{ $r->unidad_medida }} <span class="mx-1 opacity-20">|</span> Mín: {{ number_format($r->stock_minimo, 0) }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <a href="{{ route('repuestos.edit', $r->id) }}" class="flex-1 h-11 flex items-center justify-center gap-2 rounded-xl bg-primary/10 text-primary text-[0.65rem] font-black uppercase border border-primary/20 tracking-[0.15em] active:scale-[0.98] transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        Editar
                    </a>
                    @can('repuestos.eliminar')
                        <form method="POST"
                              action="{{ route('repuestos.destroy', $r->id) }}"
                              class="contents"
                              data-danger-confirm="Esta acción eliminará el producto {{ $r->codigo }} del inventario. Se conservará el historial de ventas previas."
                              data-danger-title="Eliminar producto"
                              data-danger-action-label="Sí, eliminar"
                              data-danger-icon="trash">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-11 h-11 flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 border border-red-500/20 active:scale-[0.98] transition-all">
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                    </svg>
                    <p class="font-black uppercase tracking-[0.2em] text-[0.6rem]">Sin productos hoy</p>
                </div>
            </div>
        @endforelse
    </div>
        
        @if($repuestos->hasPages())
            <div class="px-6 py-4 border-t border-border">
                {{ $repuestos->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    // El label activa el input file, el evento onchange en el input envía el form.
    // Ya está implementado inline en el HTML.
</script>
@endpush
