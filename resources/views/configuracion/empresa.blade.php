@extends('layouts.app')

@section('title', 'Configuración de Empresa')
@section('page-title', 'Identidad Corporativa')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Configuración Global</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">Definiendo la identidad y parámetros del ecosistema</p>
        </div>
        
        <div class="flex items-center gap-3">
             <a href="{{ route('dashboard') }}" class="h-10 px-4 bg-white/5 hover:bg-white/10 text-white rounded-xl border border-white/5 text-[0.6rem] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Regresar
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-primary/10 border border-primary/20 text-primary text-xs font-black uppercase tracking-widest flex items-center gap-3 animate-fade-in shadow-lg shadow-primary/5">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-black uppercase tracking-widest space-y-2 animate-fade-in shadow-lg shadow-red-500/5">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>Errores de validación detectados</span>
            </div>
            <ul class="pl-8 list-disc text-[0.6rem] opacity-80">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('config.update') }}" enctype="multipart/form-data" id="configForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- ── COLUMNA IZQUIERDA: Datos y Config ── --}}
            <div class="lg:col-span-8 space-y-8">
                
                {{-- Card: Datos Maestros --}}
                <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 relative overflow-hidden group">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary/50 to-accent/50 opacity-30"></div>
                    
                    <div class="erp-card-header !bg-transparent border-b border-white/5 p-6 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-primary border border-white/5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" /></svg>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Ficha Institucional</h2>
                            <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest mt-1">Datos legales y de contacto comercial</p>
                        </div>
                    </div>

                    <div class="erp-card-body p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Nombre --}}
                            <div class="col-span-1 md:col-span-2 space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Razón Social <span class="text-primary">*</span></label>
                                <input type="text" name="nombre_empresa" value="{{ old('nombre_empresa', $empresa?->nombre_empresa) }}" required
                                    class="form-input !bg-surface !h-12 !text-[0.75rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4 w-full tracking-wider">
                            </div>

                            {{-- RUC --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">RUC / Registro Fiscal</label>
                                <input type="text" name="ruc" value="{{ old('ruc', $empresa?->ruc) }}" placeholder="Ej: 80012345-0"
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                            </div>

                            {{-- Telefono --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Línea de Contacto</label>
                                <input type="text" name="telefono" value="{{ old('telefono', $empresa?->telefono) }}" placeholder="+595 ..."
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                            </div>

                            {{-- Email --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Email Corporativo</label>
                                <input type="email" name="email" value="{{ old('email', $empresa?->email) }}" placeholder="admin@empresa.com"
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                            </div>

                            {{-- Web --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Sitio Web</label>
                                <input type="url" name="sitio_web" value="{{ old('sitio_web', $empresa?->sitio_web) }}" placeholder="https://..."
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                            </div>

                            {{-- Direccion --}}
                            <div class="col-span-1 md:col-span-2 space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Dirección Física</label>
                                <input type="text" name="direccion" value="{{ old('direccion', $empresa?->direccion) }}" placeholder="Calle, Nro, Oficina"
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4 w-full">
                            </div>

                            {{-- Ciudad --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Ciudad</label>
                                <input type="text" name="ciudad" value="{{ old('ciudad', $empresa?->ciudad) }}"
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4">
                            </div>

                            {{-- País --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">País</label>
                                <input type="text" name="pais" value="{{ old('pais', $empresa?->pais ?? 'Paraguay') }}"
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Parámetros Operativos --}}
                <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 relative overflow-hidden group">
                    <div class="erp-card-header !bg-transparent border-b border-white/5 p-6 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-accent border border-white/5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Parámetros de Sistema</h2>
                            <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest mt-1">Configuración técnica de documentos y moneda</p>
                        </div>
                    </div>

                    <div class="erp-card-body p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="space-y-2 lg:col-span-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Moneda Base de Operación</label>
                            <select name="moneda_base" class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase">
                                <option value="USD" @selected(old('moneda_base', $empresa?->moneda_base) === 'USD')>USD — Dólar Americano</option>
                                <option value="PYG" @selected(old('moneda_base', $empresa?->moneda_base) === 'PYG')>PYG — Guaraní Paraguayo</option>
                                <option value="BRL" @selected(old('moneda_base', $empresa?->moneda_base) === 'BRL')>BRL — Real Brasileño</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Prefijo Venta</label>
                            <input type="text" name="prefijo_venta" value="{{ old('prefijo_venta', $empresa?->prefijo_venta ?? 'V') }}" maxlength="5"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Prefijo Factura</label>
                            <input type="text" name="prefijo_factura" value="{{ old('prefijo_factura', $empresa?->prefijo_factura ?? 'F') }}" maxlength="5"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4">
                        </div>

                    </div>
                </div>

            </div>

            {{-- ── COLUMNA DERECHA: Logo & Acciones ── --}}
            <div class="lg:col-span-4 space-y-8 sticky top-8">
                
                {{-- Logo Management --}}
                <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 relative overflow-hidden flex flex-col items-center p-8 text-center group">
                    <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] mb-6 block">Sello Institucional</span>
                    
                    <div class="relative w-full aspect-square md:aspect-video rounded-2xl bg-black/40 border-2 border-dashed border-white/10 flex items-center justify-center overflow-hidden hover:border-primary/50 transition-all group/logo cursor-pointer mb-6" id="dropZoneContainer">
                        @if($empresa?->logo_path)
                            <img src="{{ $empresa->logoUrl() }}" alt="Logo actual" id="logoPreview" class="max-w-[80%] max-h-[80%] object-contain drop-shadow-2xl transition-transform group-hover/logo:scale-105">
                        @else
                            <div class="flex flex-col items-center gap-4 opacity-30 group-hover/logo:opacity-100 transition-opacity" id="placeholderIcon">
                                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                <span class="text-[0.6rem] font-black uppercase tracking-widest text-white">Subir Logotipo</span>
                            </div>
                        @endif
                        <img id="newLogoPreview" class="absolute inset-0 w-full h-full object-contain p-4 hidden z-10">
                        <input type="file" name="logo" id="logoInput" class="absolute inset-0 opacity-0 cursor-pointer z-20" accept="image/*">
                    </div>

                    <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest leading-relaxed mb-6">
                        Formatos: <span class="text-white">PNG, SVG o JPG</span><br>
                        Resolución recomendada: <span class="text-white">512x512px o superior</span>
                    </p>

                    @if($empresa?->logo_path)
                        <button type="button" onclick="confirmLogoDestruction()" class="text-[0.55rem] font-black text-red-400 hover:text-red-500 uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            Revocar Logo Actual
                        </button>
                    @endif
                </div>

                {{-- Action Panel --}}
                <div class="erp-card !bg-primary/10 !border-primary/20 p-6 flex flex-col gap-4 shadow-2xl shadow-primary/10 relative overflow-hidden group">
                    <div class="absolute -right-12 -top-12 w-32 h-32 bg-primary/10 rounded-full blur-3xl pointer-events-none group-hover:scale-110 transition-transform"></div>
                    
                    <div class="flex flex-col">
                        <span class="text-[0.5rem] font-black text-primary uppercase tracking-widest mb-1 italic">Sincronización Masiva</span>
                        <span class="text-[0.7rem] font-black text-white italic uppercase tracking-tighter">Guardar Parámetros</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <button type="submit" class="h-12 w-full bg-primary hover:bg-primary/90 text-[0.7rem] font-black uppercase tracking-[0.2em] text-white rounded-xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Confirmar Cambios
                        </button>
                        
                        <a href="{{ route('dashboard') }}" class="h-10 w-full flex items-center justify-center text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest hover:text-white transition-colors">
                            Descartar Edición
                        </a>
                    </div>
                </div>

                <div class="text-[0.5rem] text-muted-foreground/40 font-bold uppercase tracking-[0.3em] text-center italic px-8">
                    ADVERTENCIA: Los cambios en la moneda base pueden afectar los cálculos de rentabilidad histórica.
                </div>

            </div>
        </div>
    </form>

    {{-- Hidden Form for Logo Destruction --}}
    @if($empresa?->logo_path)
        <form id="deleteLogoForm" method="POST" action="{{ route('config.logo.destroy') }}" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endif

    <script>
        function confirmLogoDestruction() {
            if (confirm('¿Emitir revocación del logo institucional actual?')) {
                document.getElementById('deleteLogoForm').submit();
            }
        }

        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const preview = document.getElementById('newLogoPreview');
            const placeholder = document.getElementById('placeholderIcon');
            const currentLogo = document.getElementById('logoPreview');
            
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            
            if(placeholder) placeholder.classList.add('hidden');
            if(currentLogo) currentLogo.classList.add('opacity-10');
            
            // Highlight container
            const container = document.getElementById('dropZoneContainer');
            container.style.borderColor = 'var(--primary)';
        });
    </script>
@endsection