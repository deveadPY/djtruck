@extends('layouts.app')
@section('title', 'Editar Proveedor')
@section('page-title', 'Editar Proveedor')

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
                <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Editar Aliado</h1>
                <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Actualizando perfil corporativo de {{ substr($proveedor->razon_social, 0, 20) }}...</p>
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

    <form method="POST" action="{{ route('proveedores.update', $proveedor->id) }}" class="space-y-6 max-w-4xl mx-auto">
        @csrf @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Sección Identidad --}}
            <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden h-fit shadow-2xl">
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Datos de Identidad</h2>
                </div>
                <div class="erp-card-body p-6 space-y-5">
                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Razón Social Legal *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social', $proveedor->razon_social) }}" 
                            class="form-input !bg-surface !h-12 !text-sm !font-bold rounded-xl border-white/5 focus:ring-primary/20 transition-all shadow-inner" 
                            required maxlength="200">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Nombre Comercial de Marca</label>
                        <input type="text" name="nombre_fantasia" value="{{ old('nombre_fantasia', $proveedor->nombre_fantasia) }}" 
                            class="form-input !bg-surface !h-12 !text-sm !font-bold rounded-xl border-white/5" 
                            maxlength="200">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">RUC / Registro</label>
                            <input type="text" name="ruc_rut_nit" value="{{ old('ruc_rut_nit', $proveedor->ruc_rut_nit) }}" 
                                class="form-input !bg-surface !h-12 !text-xs !font-black font-mono rounded-xl border-white/5" 
                                maxlength="30">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Jurisdicción *</label>
                            <select name="pais" class="form-input !bg-surface !h-12 !text-sm !font-black rounded-xl border-white/5" required>
                                <option value="PY" {{ old('pais', $proveedor->pais) == 'PY' ? 'selected' : '' }}>Paraguay</option>
                                <option value="BR" {{ old('pais', $proveedor->pais) == 'BR' ? 'selected' : '' }}>Brasil</option>
                                <option value="US" {{ old('pais', $proveedor->pais) == 'US' ? 'selected' : '' }}>Estados Unidos</option>
                                <option value="UY" {{ old('pais', $proveedor->pais) == 'UY' ? 'selected' : '' }}>Uruguay</option>
                                <option value="AR" {{ old('pais', $proveedor->pais) == 'AR' ? 'selected' : '' }}>Argentina</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sección Operativa & Contacto --}}
            <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden h-fit shadow-2xl">
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Configuración Comercial</h2>
                </div>
                <div class="erp-card-body p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Perfil Operativo *</label>
                            <select name="tipo" class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5" required>
                                @foreach(['DISTRIBUIDOR' => 'Repuestos', 'FABRICANTE' => 'Fabricante', 'SERVICIO' => 'Servicios', 'OTRO' => 'Generales'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('tipo', $proveedor->tipo) == $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Estado *</label>
                            <select name="activo" class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5" required>
                                <option value="1" {{ old('activo', $proveedor->activo) ? 'selected' : '' }}>🟢 ACTIVO</option>
                                <option value="0" {{ !old('activo', $proveedor->activo) ? 'selected' : '' }}>🔴 INACTIVO</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Moneda Base Operativa *</label>
                        <select name="moneda_principal" class="form-input !bg-surface !h-12 !text-sm !font-black rounded-xl border-white/5" required>
                            <option value="USD" {{ old('moneda_principal', $proveedor->moneda_principal) == 'USD' ? 'selected' : '' }}>USD (Dólar Americano)</option>
                            <option value="PYG" {{ old('moneda_principal', $proveedor->moneda_principal) == 'PYG' ? 'selected' : '' }}>PYG (Guaraní)</option>
                            <option value="BRL" {{ old('moneda_principal', $proveedor->moneda_principal) == 'BRL' ? 'selected' : '' }}>BRL (Real)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Línea Directa</label>
                            <input type="text" name="telefono" value="{{ old('telefono', $proveedor->telefono) }}" 
                                class="form-input !bg-surface !h-12 !text-xs !font-bold rounded-xl border-white/5" 
                                maxlength="50" placeholder="+595 ...">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest px-1">Email</label>
                            <input type="email" name="email" value="{{ old('email', $proveedor->email) }}" 
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-bold rounded-xl border-white/5" 
                                placeholder="contacto@corp.com">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6">
            <a href="{{ route('proveedores.index') }}" class="btn btn-ghost h-12 px-6 rounded-xl font-black uppercase tracking-widest text-[0.6rem]">Cancelar</a>
            <button type="submit" class="btn btn-primary h-12 px-8 rounded-xl shadow-lg shadow-primary/20 flex items-center gap-3 border-b-2 border-primary-hover active:translate-y-0.5 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span class="text-xs font-black uppercase tracking-wider text-white">Actualizar Perfil</span>
            </button>
        </div>
    </form>
@endsection