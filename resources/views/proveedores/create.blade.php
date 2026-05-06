@extends('layouts.app')
@section('title', 'Nuevo Proveedor')
@section('page-title', 'Registrar Proveedor')

@section('content')
    {{-- Cabecera --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('proveedores.index') }}" class="p-2.5 rounded-xl bg-surface2 border border-white/5 text-muted-foreground hover:text-primary transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div class="flex flex-col">
                <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Alta de Proveedor</h1>
                <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Vincular nuevo aliado comercial al sistema</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-start gap-3 animate-shake">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div class="flex flex-col">
                <span class="text-xs font-black text-red-500 uppercase tracking-widest">Error de Validación</span>
                <p class="text-xs text-red-400 font-bold">{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('proveedores.store') }}" class="space-y-6 max-w-4xl mx-auto">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Sección Identidad --}}
            <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden h-fit">
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Datos de Identidad</h2>
                </div>
                <div class="erp-card-body p-6 space-y-5">
                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Razón Social Legal *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" 
                            class="form-input !bg-surface !h-12 !text-sm !font-bold rounded-xl border-white/5 focus:ring-primary/20 transition-all shadow-inner" 
                            required maxlength="200" placeholder="Ej: Importadora Repuestos S.A.">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Nombre Comercial de Marca</label>
                        <input type="text" name="nombre_fantasia" value="{{ old('nombre_fantasia') }}" 
                            class="form-input !bg-surface !h-12 !text-sm !font-bold rounded-xl border-white/5" 
                            maxlength="200" placeholder="Ej: Repuestos Pro">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">RUC / Registro</label>
                            <input type="text" name="ruc_rut_nit" value="{{ old('ruc_rut_nit') }}" 
                                class="form-input !bg-surface !h-12 !text-xs !font-black font-mono rounded-xl border-white/5" 
                                maxlength="30" placeholder="000000-0">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Jurisdicción *</label>
                            <select name="pais" class="form-input !bg-surface !h-12 !text-sm !font-black rounded-xl border-white/5" required>
                                <option value="PY" {{ old('pais', 'PY') == 'PY' ? 'selected' : '' }}>Paraguay</option>
                                <option value="BR" {{ old('pais') == 'BR' ? 'selected' : '' }}>Brasil</option>
                                <option value="US" {{ old('pais') == 'US' ? 'selected' : '' }}>Estados Unidos</option>
                                <option value="UY" {{ old('pais') == 'UY' ? 'selected' : '' }}>Uruguay</option>
                                <option value="AR" {{ old('pais') == 'AR' ? 'selected' : '' }}>Argentina</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sección Operativa & Contacto --}}
            <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden h-fit">
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Configuración Comercial</h2>
                </div>
                <div class="erp-card-body p-6 space-y-5">
                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Perfil Operativo *</label>
                        <select name="tipo" class="form-input !bg-surface !h-12 !text-sm !font-black rounded-xl border-white/5" required>
                            @foreach(['DISTRIBUIDOR' => 'Distribuidor / Repuestos', 'FABRICANTE' => 'Fabricante / Origen', 'SERVICIO' => 'Servicios Técnicos', 'OTRO' => 'Gastos Operativos / Generales'] as $v => $l)
                                <option value="{{ $v }}" {{ old('tipo') == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Moneda Base de Operación *</label>
                        <select name="moneda_principal" class="form-input !bg-surface !h-12 !text-sm !font-black rounded-xl border-white/5" required>
                            <option value="USD" {{ old('moneda_principal', 'USD') == 'USD' ? 'selected' : '' }}>USD (Dólar Americano)</option>
                            <option value="PYG" {{ old('moneda_principal') == 'PYG' ? 'selected' : '' }}>PYG (Guaraní)</option>
                            <option value="BRL" {{ old('moneda_principal') == 'BRL' ? 'selected' : '' }}>BRL (Real)</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Línea de Contacto</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-muted-foreground">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                            </span>
                            <input type="text" name="telefono" value="{{ old('telefono') }}" 
                                class="form-input !bg-surface !h-12 !pl-11 !text-sm !font-bold rounded-xl border-white/5" 
                                maxlength="50" placeholder="+595 ...">
                        </div>
                    </div>

                     <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}" 
                            class="form-input !bg-surface !h-12 !text-sm !font-bold rounded-xl border-white/5" 
                            placeholder="contacto@empresa.com">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6">
            <a href="{{ route('proveedores.index') }}" class="btn btn-ghost h-12 px-6 rounded-xl font-black uppercase tracking-widest text-[0.6rem]">Descartar</a>
            <button type="submit" class="btn btn-primary h-12 px-8 rounded-xl shadow-lg shadow-primary/20 flex items-center gap-3 border-b-2 border-primary-hover active:translate-y-0.5 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="text-xs font-black uppercase tracking-wider text-white">Vincular Proveedor</span>
            </button>
        </div>
    </form>
@endsection