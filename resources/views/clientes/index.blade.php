@extends('layouts.app')
@section('title', 'Clientes')
@section('page-title', 'Directorio de Clientes')

@section('content')
    {{-- ── Cabecera y Filtros ── --}}
    <div class="space-y-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-primary/10 text-primary md:hidden">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Directorio de Clientes</h1>
                    <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold hidden md:block">Gestión de cartera de clientes y límites de crédito</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('clientes.create') }}" class="btn btn-primary flex-1 md:flex-none py-3 px-6 rounded-xl shadow-lg shadow-primary/25 border-b-2 border-primary-hover active:translate-y-0.5 transition-all text-xs font-black uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo Cliente
                </a>
            </div>
        </div>

        {{-- Buscador Estilizado --}}
        <form method="GET" action="{{ route('clientes.index') }}" class="flex gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-muted-foreground/30">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="RUC, razón social o nombre..." 
                    class="form-input !pl-11 !bg-surface/40 !backdrop-blur-md border-white/5 h-12 text-sm rounded-2xl focus:ring-primary/20 transition-all font-medium">
            </div>
            
            @if($q)
                <a href="{{ route('clientes.index') }}" class="btn btn-ghost !bg-red-500/5 !text-red-400 border-red-500/10 px-4 rounded-2xl hover:!bg-red-500/20" title="Limpiar">
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
                <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground/80">Listado de Cartera Activa</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-border/50 to-transparent"></div>
                <span class="px-2.5 py-1 rounded-lg bg-surface2 text-[0.6rem] font-black text-accent border border-white/5 uppercase tracking-tighter">
                    {{ $clientes->total() }} Clientes Registrados
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="!pl-6">RUC / ID</th>
                        <th>Razón Social / Fantasía</th>
                        <th>Contacto</th>
                        <th>Línea Crédito</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right !pr-6">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $c)
                        <tr class="hover:bg-primary/5 transition-all duration-200 group cursor-pointer" onclick="window.location='{{ route('clientes.show', $c->id) }}'">
                            <td class="!pl-6">
                                <span class="font-mono text-[0.68rem] font-black text-muted-foreground bg-surface2 px-2 py-1 rounded border border-white/5 group-hover:border-primary/30 group-hover:text-primary transition-all">
                                    {{ $c->ruc ?: 'SIN RUC' }}
                                </span>
                            </td>
                            <td>
                                <div class="font-black text-sm tracking-tight text-white group-hover:text-primary transition-colors">{{ $c->razon_social }}</div>
                                @if($c->nombre_fantasia)
                                    <div class="text-[0.65rem] text-muted-foreground uppercase font-bold tracking-widest mt-0.5 italic">{{ $c->nombre_fantasia }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 text-[0.7rem] text-muted-foreground font-medium group-hover:text-white transition-colors">
                                        <svg class="w-3.5 h-3.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.909A2.25 2.25 0 0 1 2.25 8.993V6.75m19.5 0-9 5.625L2.25 6.75" />
                                        </svg>
                                        {{ $c->email ?: 'N/A' }}
                                    </div>
                                    <div class="flex items-center gap-2 text-[0.7rem] text-muted-foreground font-black group-hover:text-accent transition-colors">
                                        <svg class="w-3.5 h-3.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                        </svg>
                                        {{ $c->telefono ?: 'N/A' }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="font-mono font-black text-sm text-accent bg-accent/5 px-2.5 py-1 rounded-lg border border-accent/10 w-fit group-hover:bg-accent/10 transition-all">
                                    $ {{ number_format($c->linea_credito_usd, 2, ',', '.') }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge-status {{ $c->activo ? 'badge-disponible' : 'badge-vendido' }} !text-[0.6rem] !px-2.5 !font-black uppercase tracking-tighter">
                                    {{ $c->activo ? 'ACTIVO' : 'INACTIVO' }}
                                </span>
                            </td>
                            <td class="text-right !pr-6" onclick="event.stopPropagation()">
                                <div class="flex justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('clientes.show', $c->id) }}" class="p-2 rounded-xl hover:bg-accent/10 text-accent transition-all" title="Ver Perfil/Estado">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('clientes.edit', $c->id) }}" class="p-2 rounded-xl hover:bg-primary/10 text-primary transition-all" title="Editar">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
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
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>
                                    <p class="font-black uppercase tracking-[0.2em] text-xs">Sin Clientes Registrados</p>
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
        @forelse($clientes as $c)
            <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-surface2/40 backdrop-blur-md p-5 transition-all duration-300 active:scale-[0.98]" onclick="window.location='{{ route('clientes.show', $c->id) }}'">
                <div class="absolute top-0 right-0 px-3 py-1 bg-surface2 rounded-bl-xl border-l border-b border-white/5">
                    <span class="badge-status {{ $c->activo ? 'badge-disponible' : 'badge-vendido' }} !text-[0.55rem] !px-1.5 !py-0 !font-black uppercase tracking-tighter">{{ $c->activo ? 'ACTIVO' : 'INACTIVO' }}</span>
                </div>

                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0 shadow-inner">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-black text-[1rem] leading-tight text-white transition-colors truncate">{{ $c->razon_social }}</div>
                        <div class="text-[0.65rem] text-muted-foreground font-mono font-black mt-1 uppercase tracking-tighter">{{ $c->ruc ?: 'SIN RUC' }}</div>
                    </div>
                </div>

                <div class="space-y-3 pt-4 border-t border-white/5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-muted-foreground/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                            <span class="text-[0.7rem] font-bold text-white/80 tracking-tight">{{ $c->telefono ?: 'No especificado' }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-[0.55rem] text-muted-foreground font-black uppercase tracking-widest mb-0.5">Línea de Crédito</div>
                            <div class="text-[0.85rem] font-black text-accent leading-none">$ {{ number_format($c->linea_credito_usd, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex gap-2" onclick="event.stopPropagation()">
                    <a href="{{ route('clientes.edit', $c->id) }}" class="flex-1 h-11 flex items-center justify-center gap-2 rounded-xl bg-surface2 text-muted-foreground text-[0.65rem] font-black uppercase border border-white/5 hover:bg-primary/10 hover:text-primary hover:border-primary/20 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        Editar Client
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-20 px-6 rounded-3xl border border-dashed border-white/10">
                <div class="flex flex-col items-center gap-2 text-muted-foreground/30">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    <p class="font-black uppercase tracking-[0.2em] text-[0.6rem]">Cartera de Clientes Vacía</p>
                </div>
            </div>
        @endforelse
    </div>
        @if($clientes->hasPages())
            <div class="px-6 py-4 border-t border-border">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
@endsection