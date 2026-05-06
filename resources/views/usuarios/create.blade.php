@extends('layouts.app')

@section('title', 'Nuevo Usuario')
@section('page-title', 'Génesis de Usuario')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Alta de Operador</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">Definiendo nuevas identidades y privilegios de acceso</p>
        </div>
        
        <div class="flex items-center gap-3">
             <a href="{{ route('usuarios.index') }}" class="h-10 px-4 bg-white/5 hover:bg-white/10 text-white rounded-xl border border-white/5 text-[0.6rem] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Regresar
            </a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto">
        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-black uppercase tracking-widest space-y-2 animate-fade-in">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    <span>Se detectaron inconsistencias</span>
                </div>
                <ul class="pl-8 list-disc text-[0.65rem] opacity-80">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-6">
            @csrf

            {{-- Card: Datos Maestros --}}
            <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 relative overflow-hidden group">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary to-accent opacity-30"></div>
                
                <div class="erp-card-header !bg-transparent border-b border-white/5 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-primary border border-white/5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Perfil del Usuario</h2>
                            <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest mt-1">Identidad básica y credenciales de correo</p>
                        </div>
                    </div>
                </div>

                <div class="erp-card-body p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Nombre --}}
                        <div class="col-span-1 md:col-span-2 space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Nombre Completo <span class="text-primary">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4 w-full">
                        </div>

                        {{-- Email --}}
                        <div class="col-span-1 md:col-span-2 space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Dirección de Email <span class="text-primary">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required 
                                class="form-input !bg-surface !h-12 !text-[0.70rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4 w-full">
                        </div>

                        {{-- Password --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Contraseña <span class="text-primary">*</span></label>
                            <input type="password" name="password" required minlength="8" autocomplete="new-password"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                        </div>

                        {{-- Password Conf --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Verificación Password <span class="text-primary">*</span></label>
                            <input type="password" name="password_confirmation" required
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Atribuciones y Estado --}}
            <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 relative overflow-hidden group">
                <div class="erp-card-header !bg-transparent border-b border-white/5 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-accent border border-white/5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75 11.25 15 15 9.75m-3-10.457L17.5 1.5l5.25 3v10.5a10.5 10.5 0 0 1-5.25 9L12 25.5l-5.25-3a10.5 10.5 0 0 1-5.25-9V3.5l5.25-2 5.25 1.043Z" /></svg>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Niveles de Privilegio</h2>
                            <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest mt-1">Designación de rol y estatus operativo</p>
                        </div>
                    </div>
                </div>

                <div class="erp-card-body p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Rol --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Perfil de Usuario <span class="text-primary">*</span></label>
                            <select name="role" required class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase">
                                <option value="">— SELECCIONAR NIVEL —</option>
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->name }}" {{ old('role') == $rol->name ? 'selected' : '' }}>
                                        {{ strtoupper($rol->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Estado --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Estatus del Acceso</label>
                            <select name="activo" class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase">
                                <option value="1" {{ old('activo', '1') == '1' ? 'selected' : '' }}>ACTIVO (ACCESO TOTAL)</option>
                                <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>INACTIVO (ACCESO DENEGADO)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center justify-end gap-6 pt-4">
                <a href="{{ route('usuarios.index') }}" class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground hover:text-white transition-colors">Abortar Operación</a>
                <button type="submit" class="h-12 px-10 bg-primary hover:bg-primary/90 text-[0.65rem] font-black uppercase tracking-[0.2em] text-white rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-95 flex items-center justify-center gap-3">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Confirmar Usuario
                </button>
            </div>
        </form>
    </div>
@endsection
