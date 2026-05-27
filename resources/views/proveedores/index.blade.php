@extends('layouts.app')
@section('title', 'Proveedores')
@section('page-title', 'Proveedores')

@section('content')
    {{-- ── Cabecera y Filtros ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight uppercase italic" style="color: var(--text)">Directorio Geográfico</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Gestión centralizada de proveedores y aliados comerciales</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('proveedores.create') }}" class="btn btn-primary h-12 px-6 rounded-xl shadow-lg shadow-primary/20 flex items-center gap-3 border-b-2 border-primary-hover active:translate-y-0.5 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-xs font-black uppercase tracking-wider">Vincular Proveedor</span>
            </a>
        </div>
    </div>

    {{-- Buscador y Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-8">
        <div class="lg:col-span-3">
            <form method="GET" action="{{ route('proveedores.index') }}" class="relative group">
                <span class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" 
                    placeholder="Búsqueda global: RUC, Razón Social, Marca..." 
                    class="form-input !bg-surface/60 !backdrop-blur-md !pl-12 !h-14 !text-sm !font-bold rounded-2xl transition-all shadow-xl"
                    style="border-color: var(--border)"
                    autocomplete="off">
                @if($q)
                    <a href="{{ route('proveedores.index') }}" class="absolute inset-y-0 right-4 flex items-center text-muted-foreground hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </form>
        </div>
        <div class="hidden lg:flex items-center justify-between p-4 bg-surface2/60 backdrop-blur-md rounded-2xl shadow-xl border" style="border-color: var(--border)">
            <div class="flex flex-col">
                <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest">Base de Datos</span>
                <span class="text-lg font-black italic" style="color: var(--text)">{{ $proveedores->total() }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary border" style="border-color: var(--primary)">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-2.123-7.674 4.125 4.125 0 0 0-4.618 6.255Z" />
                    <path d="M15 8.25a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM3.751 20.117a15.921 15.921 0 0 1 11.25-4.867 15.92 15.92 0 0 1 11.25 4.867A15.921 15.921 0 0 1 3.75 20.117Z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- ── Vista Escritorio (Table Master) ── --}}
    <div class="hidden md:block erp-card !bg-surface/60 !backdrop-blur-xl relative overflow-hidden shadow-2xl" style="border-color: var(--border)">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr class="text-[0.6rem] uppercase tracking-widest">
                        <th class="!pl-6">Identidad Comercial</th>
                        <th>Registro Fiscal</th>
                        <th>Perfil</th>
                        <th>Jurisdicción</th>
                        <th>Contacto</th>
                        <th class="text-right !pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proveedores as $p)
                        <tr class="hover:bg-primary/5 transition-all duration-200 group cursor-pointer" onclick="window.location='{{ route('proveedores.show', $p->id) }}'">
                            <td class="!pl-6">
                                <div class="flex flex-col">
                                    <span class="font-black text-sm group-hover:text-primary transition-colors uppercase italic" style="color: var(--text)">{{ $p->razon_social }}</span>
                                    @if($p->nombre_fantasia)
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-primary/40"></span>
                                            <span class="text-[0.65rem] text-muted-foreground font-bold tracking-tight">{{ $p->nombre_fantasia }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="font-mono text-[0.7rem] font-black text-muted-foreground bg-surface2 px-2 py-1 rounded border border-white/5 w-fit">
                                    {{ $p->ruc_rut_nit ?: 'SIN REGISTRO' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge-status {{ $p->tipo === 'DISTRIBUIDOR' ? 'badge-disponible' : ($p->tipo === 'SERVICIO' ? 'badge-observacion' : 'badge-preparacion') }} !text-[0.55rem] !px-2 !font-black uppercase tracking-widest">
                                    {{ $p->tipo }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-4 rounded overflow-hidden shadow-sm border border-white/5">
                                        @if($p->pais === 'PY')
                                            <img src="https://flagcdn.com/w40/py.png" class="w-full h-full object-cover">
                                        @elseif($p->pais === 'BR')
                                            <img src="https://flagcdn.com/w40/br.png" class="w-full h-full object-cover">
                                        @else
                                            <div class="bg-surface3 w-full h-full flex items-center justify-center text-[0.5rem] font-bold">{{ $p->pais }}</div>
                                        @endif
                                    </div>
                                    <span class="text-[0.65rem] font-black uppercase tracking-tighter" style="color: var(--text-muted)">{{ $p->moneda_principal }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold" style="color: var(--text)">{{ $p->telefono ?: '—' }}</span>
                                    <span class="text-[0.6rem] text-muted-foreground truncate max-w-[120px]">{{ $p->email }}</span>
                                </div>
                            </td>
                            <td class="text-right !pr-6" onclick="event.stopPropagation()">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('proveedores.edit', $p->id) }}" class="p-2 rounded-xl hover:bg-white/5 text-muted-foreground hover:text-white transition-all">
                                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-20">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-2.123-7.674 4.125 4.125 0 0 0-4.618 6.255Z" />
                                    </svg>
                                    <p class="font-black uppercase tracking-[0.2em] text-xs">Sin registros que coincidan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Vista Móvil (Grid Cards) ── --}}
    <div class="md:hidden space-y-4">
        @forelse($proveedores as $p)
            <div class="relative overflow-hidden rounded-3xl border bg-surface/60 backdrop-blur-md p-5 transition-all duration-300 active:scale-[0.98] shadow-xl" 
                style="border-color: var(--border)"
                onclick="window.location='{{ route('proveedores.show', $p->id) }}'">
                <div class="absolute top-0 right-0 p-3 flex gap-2">
                    <div class="w-6 h-4 rounded overflow-hidden shadow-sm border" style="border-color: var(--border)">
                        <img src="https://flagcdn.com/w40/{{ strtolower($p->pais) }}.png" class="w-full h-full object-cover">
                    </div>
                </div>

                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0 shadow-inner border border-primary/10">
                        <span class="text-lg font-black uppercase italic">{{ substr($p->razon_social, 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full {{ $p->tipo === 'DISTRIBUIDOR' ? 'bg-green-500' : 'bg-primary' }}"></span>
                            {{ $p->tipo }}
                        </div>
                        <div class="font-black text-base leading-tight mb-1 truncate uppercase italic" style="color: var(--text)">{{ $p->razon_social }}</div>
                        <div class="font-mono text-[0.65rem] text-muted-foreground font-bold tracking-tight">RUC: {{ $p->ruc_rut_nit ?: '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t" style="border-color: var(--border)">
                    <div class="flex flex-col">
                        <span class="text-[0.5rem] font-black text-muted-foreground uppercase tracking-[0.2em] mb-1">Contacto Directo</span>
                        <span class="text-xs font-bold truncate" style="color: var(--text)">{{ $p->telefono ?: 'No disp.' }}</span>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[0.5rem] font-black text-muted-foreground uppercase tracking-[0.2em] mb-1">Operativa</span>
                        <span class="text-xs font-black text-primary">{{ $p->moneda_principal }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-16 px-6 rounded-3xl border border-dashed border-white/10">
                <p class="font-black uppercase tracking-[0.2em] text-[0.6rem] text-muted-foreground/30">Sin aliados comerciales</p>
            </div>
        @endforelse
    </div>

    @if($proveedores->hasPages())
        <div class="mt-8">
            {{ $proveedores->links() }}
        </div>
    @endif

@endsection