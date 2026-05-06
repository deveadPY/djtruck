@extends('layouts.app')

@php $isEdit = isset($role); @endphp

@section('title', ($isEdit ? 'Editar' : 'Crear') . ' Perfil de Seguridad')
@section('page-title', 'Gobernanza RBAC')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex flex-col">
            <h1 class="text-xl font-black text-white italic uppercase tracking-wider">
                {{ $isEdit ? "Modificar Perfil: {$role->name}" : "Crear Nuevo Perfil de Acceso" }}
            </h1>
            <p class="text-[0.6rem] text-muted-foreground uppercase tracking-widest font-bold italic">Configuración de matriz de interoperabilidad y seguridad</p>
        </div>
        <a href="{{ route('roles.index') }}" class="text-[0.6rem] font-black text-white/50 hover:text-white uppercase tracking-widest transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            Volver al Listado
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 text-[0.65rem] font-black uppercase tracking-widest animate-fade-in shadow-lg shadow-red-500/5">
           @foreach($errors->all() as $e) <div class="flex items-center gap-2"><div class="w-1 h-1 bg-current rounded-full"></div> {{ $e }}</div> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('roles.update', $role) : route('roles.store') }}" class="space-y-8">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Datos Básicos --}}
        <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5">
            <div class="erp-card-header border-b border-white/5 !bg-transparent p-6">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-6 bg-primary rounded-full"></div>
                    <h3 class="text-[0.7rem] font-black text-white uppercase italic tracking-widest">Identificación del Perfil</h3>
                </div>
            </div>
            <div class="erp-card-body p-8">
                <div class="max-w-md">
                    <label class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest mb-2 block italic">Etiqueta del Rol (System Name)</label>
                    <div class="relative">
                        <input type="text" name="name" value="{{ old('name', $isEdit ? $role->name : '') }}" required
                            class="w-full bg-black/40 border border-white/10 focus:border-primary/50 text-white text-sm rounded-xl px-5 py-3 outline-none transition-all placeholder:text-white/10 font-bold italic"
                            placeholder="Ej: Auditor Junior, Gerente de Ventas...">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-white/10">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Matriz de Permisos --}}
        <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5">
            <div class="erp-card-header border-b border-white/5 !bg-transparent p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-6 bg-accent rounded-full"></div>
                    <div class="flex flex-col">
                        <h3 class="text-[0.7rem] font-black text-white uppercase italic tracking-widest">Capacidades y Accesos</h3>
                        <span class="text-[0.5rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Distribución granular de permisos</span>
                    </div>
                </div>
                
                <button type="button" id="selectAll" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white rounded-xl border border-white/10 text-[0.55rem] font-black uppercase tracking-widest transition-all italic">
                    Seleccionar Todo el Ecosistema
                </button>
            </div>
            <div class="erp-card-body p-0">
                @include('roles._permission-matrix', ['modules' => $modules, 'allActions' => $allActions, 'rolePerms' => $rolePerms])
            </div>
        </div>

        {{-- Action Panel --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('roles.index') }}" class="h-12 px-8 flex items-center justify-center text-[0.65rem] font-black text-white/50 hover:text-white uppercase tracking-widest transition-all">
                Cancelar Gestión
            </a>
            <button type="submit" class="h-12 px-10 bg-primary hover:bg-primary/90 text-white rounded-xl shadow-xl shadow-primary/20 text-[0.65rem] font-black uppercase tracking-[0.2em] transition-all active:scale-95 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M17.598 2.304L4.854 15.048a1 1 0 00-.233.388l-1.458 5.833a1 1 0 001.213 1.213l5.833-1.458a1 1 0 00.388-.233L23.442 7.947a3.375 3.375 0 00-4.844-4.844z" /></svg>
                Comprometer Cambios
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('click', function() {
    const cbs = document.querySelectorAll('input[name="permissions[]"]');
    const someUnchecked = [...cbs].some(c => !c.checked);
    cbs.forEach(cb => cb.checked = someUnchecked);
    this.innerText = someUnchecked ? 'Deseleccionar Todo' : 'Seleccionar Todo el Ecosistema';
});
</script>
@endsection
