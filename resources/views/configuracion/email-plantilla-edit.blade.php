@extends('layouts.app')
@section('title', 'Editor de Plantilla — ' . ($plantilla->nombre ?? 'Nueva'))
@section('page-title', 'Diseñador de Comunicaciones')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">
                {{ isset($plantilla->id) ? 'Refinar Plantilla' : 'Génesis de Plantilla' }}
            </h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">
                {{ $plantilla->nombre ?? 'Definiendo nueva estructura de comunicación' }}
            </p>
        </div>
        
        <div class="flex items-center gap-3">
             <a href="{{ route('config.email') }}" class="h-10 px-4 bg-white/5 hover:bg-white/10 text-white rounded-xl border border-white/5 text-[0.6rem] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Regresar
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-black uppercase tracking-widest space-y-2 animate-fade-in">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>Existen inconsistencias en los campos</span>
            </div>
            <ul class="pl-8 list-disc text-[0.65rem] opacity-80">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ isset($plantilla->id) ? route('config.email.plantilla.update', $plantilla->id) : route('config.email.plantilla.store') }}" id="plantillaForm">
        @csrf
        @if(isset($plantilla->id)) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- ── COLUMNA IZQUIERDA: Editor & Config ── --}}
            <div class="lg:col-span-7 space-y-6">
                
                {{-- Metadata --}}
                <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 overflow-hidden">
                    <div class="erp-card-header !bg-transparent border-b border-white/5 p-6">
                        <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Identidad de la Comunicación</h2>
                    </div>
                    <div class="erp-card-body p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Título Interno</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $plantilla->nombre) }}" required
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4">
                        </div>
                        
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Asunto del Email</label>
                            <input type="text" name="asunto" value="{{ old('asunto', $plantilla->asunto) }}" required
                                placeholder="Ej: Confirmación de Pago - [referencia]"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                        </div>

                        @if(!isset($plantilla->id))
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Código de Sistema</label>
                                <input type="text" name="tipo" value="{{ old('tipo', $plantilla->tipo) }}" required
                                    placeholder="TIPO_EVENTO"
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase px-4">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Tokens Disponibles (JSON)</label>
                                <input type="text" name="variables_disponibles" value="{{ old('variables_disponibles', $plantilla->variables_disponibles) }}"
                                    placeholder='["cliente", "monto"]'
                                    class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white px-4">
                            </div>
                        @endif

                        <div class="md:col-span-2 pt-2">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative w-10 h-5">
                                    <input type="hidden" name="activo" value="0">
                                    <input type="checkbox" name="activo" value="1" {{ old('activo', $plantilla->activo) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-full h-full bg-white/5 border border-white/10 rounded-full peer-checked:bg-primary transition-all"></div>
                                    <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-all peer-checked:left-6"></div>
                                </div>
                                <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest group-hover:text-white transition-colors">Plantilla Operativa (En uso)</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Variable Injector --}}
                <div class="erp-card !bg-surface/20 !backdrop-blur-md !border-white/5 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-[0.6rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic">Inyección de Tokens Dinámicos</h3>
                        <span class="text-[0.5rem] font-bold text-primary animate-pulse uppercase">Click para Insertar</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @php 
                            $vars = is_string($plantilla->variables_disponibles) ? json_decode($plantilla->variables_disponibles, true) : ($plantilla->variables_disponibles ?? []);
                            if(!is_array($vars)) $vars = [];
                        @endphp
                        @foreach($vars as $var)
                            <button type="button" class="var-chip h-8 px-3 rounded-lg bg-surface border border-white/5 text-[0.65rem] font-black text-white italic hover:text-primary hover:border-primary/40 hover:scale-105 transition-all shadow-lg active:scale-95" data-var="{{ $var }}">
                                &#123;&#123; {{ $var }} &#125;&#125;
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Code Editor --}}
                <div class="erp-card !bg-[#0f111a] !border-white/5 overflow-hidden flex flex-col shadow-2xl min-h-[500px]">
                    <div class="erp-card-header !bg-black/20 border-b border-white/5 p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-500/40"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/40"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-green-500/40"></div>
                            </div>
                            <span class="text-[0.55rem] font-bold text-white/30 uppercase tracking-[0.3em] font-mono ml-4">EDITOR_HTML_V1.0</span>
                        </div>
                        <span class="text-[0.5rem] font-black text-primary/40 uppercase font-mono tracking-widest">Syntax: Raw HTML</span>
                    </div>
                    <textarea name="cuerpo_html" id="cuerpo_html" spellcheck="false" required
                        class="flex-1 w-full !bg-transparent !border-0 text-white font-mono text-xs leading-relaxed p-6 focus:ring-0 outline-none resize-none cursor-auto overflow-y-auto custom-scrollbar">{{ old('cuerpo_html', $plantilla->cuerpo_html) }}</textarea>
                </div>

            </div>

            {{-- ── COLUMNA DERECHA: Live Preview & Save ── --}}
            <div class="lg:col-span-5 space-y-6 sticky top-8">
                
                {{-- Action Bar --}}
                <div class="erp-card !bg-primary/10 !border-primary/20 p-4 flex items-center justify-between shadow-2xl shadow-primary/10">
                    <div class="flex flex-col">
                        <span class="text-[0.5rem] font-black text-primary uppercase tracking-widest">Sincronización</span>
                        <span class="text-[0.7rem] font-black text-white italic uppercase tracking-tighter">Guardar Cambios</span>
                    </div>
                    <button type="submit" class="h-10 px-6 bg-primary hover:bg-primary/90 text-[0.65rem] font-black uppercase tracking-[0.2em] text-white rounded-xl shadow-lg transition-all active:scale-95 flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Serializar Data
                    </button>
                </div>

                {{-- Live Preview Frame --}}
                <div class="erp-card !bg-white !border-transparent overflow-hidden shadow-2xl flex flex-col h-[calc(100vh-280px)] min-h-[500px]">
                    <div class="bg-gray-100 p-3 border-b border-gray-200 flex items-center justify-between">
                        <div class="flex gap-2">
                             <div class="w-8 h-2.5 rounded-full bg-gray-300"></div>
                             <div class="w-12 h-2.5 rounded-full bg-gray-300"></div>
                        </div>
                        <span class="text-[0.55rem] font-black text-gray-400 uppercase tracking-widest">Live Viewport</span>
                    </div>
                    <iframe id="previewFrame" class="w-full h-full border-0" sandbox="allow-same-origin"></iframe>
                </div>

                <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest text-center italic opacity-40 px-6">
                    Advertencia: El cuerpo HTML debe ser compatible con clientes de correo móviles. Evite JavaScript y CSS externo complejo.
                </p>

            </div>

        </div>
    </form>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); }
    </style>

    <script>
    (function () {
        const textarea = document.getElementById('cuerpo_html');
        const iframe   = document.getElementById('previewFrame');

        function updatePreview() {
            try {
                iframe.srcdoc = textarea.value;
            } catch(e) {}
        }

        textarea.addEventListener('input', updatePreview);
        updatePreview();

        document.querySelectorAll('.var-chip').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = '{{' + this.dataset.var + '}}';
                var pos  = textarea.selectionStart;
                var end  = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, pos) + text + textarea.value.substring(end);
                textarea.selectionStart = textarea.selectionEnd = pos + text.length;
                textarea.focus();
                updatePreview();
            });
        });
    }());
    </script>
@endsection
