@extends('layouts.app')

@section('title', 'Gobernanza de Roles')
@section('page-title', 'Seguridad y Acceso')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Privilegios de Sistema</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">Definición de perfiles de acceso y matriz de permisos RBAC</p>
        </div>
        
        <div class="flex items-center gap-3">
             @can('roles.crear')
                <a href="{{ route('roles.create') }}" class="h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-xl shadow-lg shadow-primary/20 text-[0.6rem] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-3 active:scale-95">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Nuevo Perfil de Acceso
                </a>
             @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-primary/10 border border-primary/20 text-primary text-xs font-black uppercase tracking-widest flex items-center gap-3 animate-fade-in shadow-lg shadow-primary/5">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-black uppercase tracking-widest flex items-center gap-3 animate-fade-in shadow-lg shadow-red-500/5">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Grid de Roles ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($roles as $rol)
            <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 relative overflow-hidden group hover:!border-primary/40 transition-all duration-300 shadow-2xl">
                {{-- Decoración --}}
                <div class="absolute -right-8 -top-8 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
                
                <div class="p-6 space-y-6">
                    {{-- Identity --}}
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <h3 class="text-[0.85rem] font-black text-white uppercase italic tracking-wider">{{ $rol->name }}</h3>
                            <span class="text-[0.5rem] font-black text-muted-foreground uppercase tracking-[0.2em] mt-1 italic">Web Security Guard</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-primary border border-white/5 group-hover:border-primary/30 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75L11.25 15 15 9.75m-3-10.457L17.5 1.5l5.25 3v10.5a10.5 10.5 0 01-5.25 9L12 25.5l-5.25-3a10.5 10.5 0 01-5.25-9V3.5l5.25-2 5.25 1.043z" /></svg>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 rounded-xl bg-black/20 border border-white/5">
                            <span class="text-[0.5rem] font-black text-muted-foreground uppercase block mb-1">Usuarios</span>
                            <span class="text-xl font-black text-white italic tracking-tighter">{{ $rol->users_count }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-black/20 border border-white/5">
                            <span class="text-[0.5rem] font-black text-muted-foreground uppercase block mb-1">Permisos</span>
                            <span class="text-xl font-black text-accent italic tracking-tighter">{{ $rol->permissions_count }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-2">
                        @can('roles.editar')
                            <a href="{{ route('roles.edit', $rol) }}" class="flex-1 h-10 bg-white/5 hover:bg-primary/20 text-white rounded-xl border border-white/5 hover:border-primary/30 text-[0.55rem] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                Matriz de Acceso
                            </a>
                        @endcan
                        
                        @can('roles.eliminar')
                            @if($rol->users_count == 0)
                                <form method="POST" action="{{ route('roles.destroy', $rol) }}" class="inline" onsubmit="return confirm('¿Eliminar el rol {{ $rol->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-10 h-10 rounded-xl bg-red-500/5 hover:bg-red-500/20 text-red-500 border border-red-500/10 flex items-center justify-center transition-all active:scale-90">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 01 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center opacity-20 italic">
                <span class="text-[0.6rem] font-black uppercase tracking-[0.5em]">No se detectan perfiles de acceso definidos</span>
            </div>
        @endforelse
    </div>
@endsection
