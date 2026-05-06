@extends('layouts.app')

@section('title', 'Gestión de Usuarios')
@section('page-title', 'Seguridad & Usuarios')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Directorio Maestro</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">Control de accesos y perfiles de seguridad</p>
        </div>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
            <form method="GET" action="{{ route('usuarios.index') }}" class="relative w-full md:w-80 group">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar identidad..." 
                    class="w-full bg-surface2/40 backdrop-blur-md rounded-2xl border border-white/5 py-2.5 pl-10 pr-4 text-[0.7rem] font-black text-white placeholder:text-muted-foreground/50 focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all uppercase tracking-widest">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground/50 group-focus-within:text-primary transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
            </form>

            @can('usuarios.crear')
                <a href="{{ route('usuarios.create') }}" class="w-full md:w-auto h-11 px-6 bg-primary hover:bg-primary/90 text-[0.65rem] font-black uppercase tracking-[0.2em] text-white rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-95 flex items-center justify-center gap-3">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo Operador
                </a>
            @endcan
        </div>
    </div>

    {{-- ── Lista de Usuarios ── --}}
    <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 shadow-2xl relative overflow-hidden">
        <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
        
        <div class="erp-card-header !bg-transparent border-b border-white/5 p-6 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-2 h-6 bg-primary rounded-full"></div>
                <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Usuarios Verificados</h2>
            </div>
            <span class="text-[0.55rem] font-black text-primary bg-primary/10 px-3 py-1 rounded-full border border-primary/20 uppercase tracking-widest italic animate-pulse">
                {{ $users->total() }} Registros
            </span>
        </div>

        {{-- Desktop View --}}
        <div class="overflow-x-auto hidden md:block relative z-10">
            <table class="erp-table text-xs">
                <thead>
                    <tr class="text-[0.6rem] uppercase tracking-widest text-muted-foreground border-b border-white/5">
                        <th class="!pl-8">Identidad Digital</th>
                        <th>Email de Acceso</th>
                        <th class="text-center">Rol Asignado</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right !pr-8">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php 
                            $esEliminado = $user->trashed(); 
                            $esInactivo = !$user->activo; 
                            $roleName = $user->roles->first()?->name ?? $user->role ?? '—';
                        @endphp
                        <tr class="hover:bg-primary/5 group transition-all duration-200 {{ $esEliminado ? 'opacity-30' : '' }}">
                            <td class="!pl-8">
                                <div class="flex items-center gap-4 py-1">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-surface2 to-surface3 border border-white/5 flex items-center justify-center font-black text-xs text-primary shadow-xl group-hover:scale-110 transition-transform">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[0.75rem] font-black text-white italic uppercase truncate tracking-tighter">{{ $user->name }}</span>
                                        @if($user->id === auth()->id())
                                            <span class="text-[0.5rem] font-black text-accent uppercase tracking-widest mt-0.5">Sesión Actual</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted-foreground/70 font-mono tracking-tighter">{{ $user->email }}</td>
                            <td class="text-center">
                                <span class="text-[0.6rem] font-black px-3 py-1 rounded-lg bg-surface3/50 text-white/50 border border-white/5 uppercase italic tracking-widest">
                                    {{ $roleName }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($esEliminado)
                                    <span class="badge-status !bg-red-500/10 !text-red-500 !border-red-500/20 !text-[0.55rem] font-black italic">ELIMINADO</span>
                                @elseif($esInactivo)
                                    <span class="badge-status !bg-yellow-500/10 !text-yellow-500 !border-yellow-500/20 !text-[0.55rem] font-black italic">INACTIVO</span>
                                @else
                                    <span class="badge-status !bg-primary/10 !text-primary !border-primary/20 !text-[0.55rem] font-black italic">ACTIVO</span>
                                @endif
                            </td>
                            <td class="text-right !pr-8">
                                <div class="flex justify-end gap-2">
                                    @can('usuarios.editar')
                                        @if(!$esEliminado)
                                            <a href="{{ route('usuarios.edit', $user) }}" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-primary/20 text-muted-foreground hover:text-primary border border-white/5 transition-all flex items-center justify-center" title="Editar Perfil">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                </svg>
                                            </a>
                                        @endif

                                        <form method="POST" action="{{ route('usuarios.toggle', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-white/5 {{ $esEliminado || $esInactivo ? 'hover:bg-primary/20 text-primary' : 'hover:bg-yellow-500/20 text-yellow-500' }} border border-white/5 transition-all flex items-center justify-center" 
                                                title="{{ $esEliminado ? 'Restaurar Sistema' : ($esInactivo ? 'Activar Acceso' : 'Suspender Acceso') }}">
                                                @if($esEliminado || $esInactivo)
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path d="M5 13l4 4L19 7" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>
                                    @endcan

                                    @can('usuarios.eliminar')
                                        @if(!$esEliminado && $user->id !== auth()->id())
                                            <form method="POST" action="{{ route('usuarios.destroy', $user) }}" class="inline" onsubmit="return confirm('¿Confirmar purga del usuario {{ $user->name }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-red-500/20 text-muted-foreground hover:text-red-500 border border-white/5 transition-all flex items-center justify-center" title="Eliminar Definitivamente">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-24">
                                <div class="flex flex-col items-center gap-4 opacity-20 italic">
                                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-2.533-4.656 6.915 6.915 0 0 1-4.745-4.444 3.375 3.375 0 0 0-6.508 0 6.914 6.914 0 0 1-4.745 4.444 4.125 4.125 0 0 0-2.533 4.656 9.307 9.307 0 0 0 4.122.952 9.412 9.412 0 0 0 2.625-.372M7.5 19.128v-3m9 3v-3M9 20.25h6" /></svg>
                                    <span class="text-xs font-black uppercase tracking-[0.4em]">Sin identidades registradas</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile View --}}
        <div class="md:hidden grid grid-cols-1 gap-4 p-4 relative z-10">
            @forelse($users as $user)
                @php 
                    $esEliminado = $user->trashed(); 
                    $esInactivo = !$user->activo; 
                    $roleName = $user->roles->first()?->name ?? $user->role ?? '—';
                @endphp
                <div class="p-5 rounded-2xl bg-surface2/30 border border-white/5 space-y-4 shadow-xl {{ $esEliminado ? 'opacity-30' : '' }}">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-surface2 to-surface3 border border-white/5 flex items-center justify-center font-black text-xs text-primary shadow-xl">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="text-[0.8rem] font-black text-white italic uppercase truncate tracking-tighter">{{ $user->name }}</span>
                            <span class="text-[0.6rem] text-muted-foreground font-mono opacity-50 truncate">{{ $user->email }}</span>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="text-[0.5rem] font-black px-2 py-0.5 rounded bg-surface3 text-white/50 border border-white/5 uppercase italic">{{ $roleName }}</span>
                            @if($user->id === auth()->id())
                                <span class="text-[0.5rem] font-black text-accent uppercase tracking-widest">TÚ</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-white/5">
                        <div>
                            @if($esEliminado)
                                <span class="badge-status !bg-red-500/10 !text-red-500 !border-red-500/20 !text-[0.5rem] font-black italic">ELIMINADO</span>
                            @elseif($esInactivo)
                                <span class="badge-status !bg-yellow-500/10 !text-yellow-500 !border-yellow-500/20 !text-[0.5rem] font-black italic">INACTIVO</span>
                            @else
                                <span class="badge-status !bg-primary/10 !text-primary !border-primary/20 !text-[0.5rem] font-black italic">ACTIVO</span>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-2">
                            @can('usuarios.editar')
                                @if(!$esEliminado)
                                    <a href="{{ route('usuarios.edit', $user) }}" class="w-9 h-9 rounded-xl bg-white/5 text-muted-foreground flex items-center justify-center border border-white/5">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('usuarios.toggle', $user) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="w-9 h-9 rounded-xl bg-white/5 {{ $esEliminado || $esInactivo ? 'text-primary' : 'text-yellow-500' }} flex items-center justify-center border border-white/5">
                                        @if($esEliminado || $esInactivo)
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path d="M5 13l4 4L19 7" /></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        @endif
                                    </button>
                                </form>
                            @endcan
                            @can('usuarios.eliminar')
                                @if(!$esEliminado && $user->id !== auth()->id())
                                    <form method="POST" action="{{ route('usuarios.destroy', $user) }}" class="inline" onsubmit="return confirm('¿Borrar usuario?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-9 h-9 rounded-xl bg-white/5 text-red-500 flex items-center justify-center border border-white/5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79" /></svg>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 opacity-20 font-black uppercase text-[0.6rem] tracking-widest italic tracking-[0.5em]">Sin Registros Corrientes</div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="px-8 py-4 border-t border-white/5 overflow-x-auto scrollbar-hide relative z-10">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
