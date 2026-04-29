@extends('layouts.app')
@section('title', 'Registrar Egreso')
@section('page-title', 'Registrar Egreso')

@section('content')
    <div class="max-w-4xl mx-auto">
        {{-- Cabecera --}}
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('facturas.index') }}" class="p-2.5 rounded-xl bg-surface2 border border-white/5 text-muted-foreground hover:text-primary transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Registrar Egreso</h1>
                <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Carga de facturas, tickets y gastos operativos</p>
            </div>
        </div>

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold mb-6">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('facturas.store') }}" enctype="multipart/form-data" data-confirm="¿Desea registrar esta operación de egreso?">
            @csrf
            
            <div class="space-y-6">
                {{-- Sección 1: Datos Base --}}
                <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden">
                    <div class="erp-card-header !bg-transparent border-b border-white/5">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Detalles del Documento</h2>
                    </div>
                    <div class="erp-card-body p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Proveedor --}}
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Proveedor / Beneficiario *</label>
                                <div class="flex gap-2">
                                    <select name="proveedor_id" id="proveedor_sel" class="form-input !bg-surface !h-12 !text-xs !font-bold rounded-xl border-white/5 focus:ring-primary/20" required>
                                        <option value="">— SELECCIONAR PROVEEDOR —</option>
                                        @foreach($proveedores as $p)
                                            <option value="{{ $p->id }}" @selected(old('proveedor_id') == $p->id)>{{ $p->razon_social }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="$dispatch('open-modal-prov')" class="p-3 bg-primary/10 text-primary rounded-xl border border-primary/20 hover:bg-primary/20 transition-all shadow-sm">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Nº Factura --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">N° Comprobante *</label>
                                <input type="text" name="numero_factura" value="{{ old('numero_factura') }}" required maxlength="50"
                                    class="form-input !bg-surface !h-12 !text-xs !font-black !tracking-widest uppercase rounded-xl border-white/5" placeholder="000-000-0000000">
                            </div>

                            {{-- Fecha --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Fecha de Operación *</label>
                                <input type="date" name="fecha_factura" value="{{ old('fecha_factura', date('Y-m-d')) }}" required
                                    class="form-input !bg-surface !h-12 !text-xs !font-black rounded-xl border-white/5">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sección 2: Aplicación del Gasto --}}
                <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden">
                    <div class="erp-card-header !bg-transparent border-b border-white/5">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Aplicación y Destino</h2>
                    </div>
                    <div class="erp-card-body p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Destino --}}
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Tipo de Gasto *</label>
                                <select name="destino" id="destino_sel" onchange="toggleDestino()" required
                                    class="form-input !bg-surface !h-12 !text-xs !font-bold rounded-xl border-white/5">
                                    <option value="GASTO_OPERATIVO" @selected(old('destino', request('vehiculo_id') ? 'VEHICULO' : 'GASTO_OPERATIVO') == 'GASTO_OPERATIVO')>🏢 GASTO OPERATIVO / LOCAL</option>
                                    <option value="VEHICULO" @selected(old('destino', request('vehiculo_id') ? 'VEHICULO' : '') == 'VEHICULO')>🚛 COSTO VEHÍCULO (ACTIVO)</option>
                                    <option value="MIXTO" @selected(old('destino') == 'MIXTO')>🔄 MIXTO / VARIOS</option>
                                </select>
                            </div>

                            {{-- Concepto --}}
                            <div class="space-y-2" id="cuenta_container">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Concepto / Referencia *</label>
                                <input type="text" name="cuenta_gasto" id="cuenta_gasto" value="{{ old('cuenta_gasto') }}"
                                    class="form-input !bg-surface !h-12 !text-xs !font-bold rounded-xl border-white/5" placeholder="Ej: Pago de Alquiler, Reparación Motor...">
                            </div>

                            {{-- Vehículo --}}
                            <div class="space-y-2" id="vehiculo_container" style="display:none">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Seleccionar Camión *</label>
                                <select name="vehiculo_id" id="vehiculo_id" class="form-input !bg-surface !h-12 !text-xs !font-bold rounded-xl border-white/5">
                                    <option value="">— SELECCIONAR UNIDAD —</option>
                                    @foreach($vehiculos as $v)
                                        <option value="{{ $v->id }}" @selected(old('vehiculo_id', request('vehiculo_id')) == $v->id)>
                                            {{ $v->marca }} {{ $v->modelo }} ({{ substr($v->numero_chasis, -6) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Categoría Vehículo --}}
                            <div class="space-y-2" id="categoria_vehiculo_container" style="display:none">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Rubro de Inversión *</label>
                                <select name="categoria_gasto" id="categoria_gasto" class="form-input !bg-surface !h-12 !text-xs !font-bold rounded-xl border-white/5">
                                    @foreach(['REPARACION_MECANICA' => 'Mecánica', 'CHAPERIA_PINTURA' => 'Estética', 'ELECTRICIDAD' => 'Inst. Eléctrica', 'NEUMATICOS' => 'Cubiertas', 'DERECHOS_ADUANA' => 'Aduana', 'IMPUESTO_IMPORTACION' => 'Impuestos', 'LOGISTICA' => 'Flete', 'DOCUMENTACION' => 'Escribanía', 'OTROS_PREPARACION' => 'Otros'] as $val => $lbl)
                                        <option value="{{ $val }}" @selected(old('categoria_gasto', 'OTROS_PREPARACION') == $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sección 3: Liquidación --}}
                <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden">
                    <div class="erp-card-header !bg-transparent border-b border-white/5 flex items-center justify-between">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Importes y Moneda</h2>
                        <div class="flex items-center gap-2">
                             @foreach(['USD', 'PYG', 'BRL'] as $m)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="moneda" value="{{ $m }}" @checked(old('moneda', 'PYG') == $m) onchange="calcularTotalUsd()" class="hidden peer">
                                    <span class="px-3 py-1 rounded-lg text-[0.6rem] font-black bg-surface3 border border-white/5 text-muted-foreground peer-checked:bg-primary/20 peer-checked:text-primary peer-checked:border-primary/30 transition-all">{{ $m }}</span>
                                </label>
                             @endforeach
                        </div>
                    </div>
                    <div class="erp-card-body p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Subtotal (Neto) *</label>
                                <input type="number" name="subtotal" value="{{ old('subtotal') }}" step="0.01" min="0" required oninput="calcularTotalUsd()"
                                    class="form-input !bg-surface !h-12 !text-sm !font-black rounded-xl border-white/5 text-white">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">IVA / Impuestos</label>
                                <input type="number" name="impuestos" value="{{ old('impuestos', 0) }}" step="0.01" min="0" oninput="calcularTotalUsd()"
                                    class="form-input !bg-surface !h-12 !text-sm !font-black rounded-xl border-white/5 text-white">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Equivalente USD *</label>
                                <div class="relative">
                                    <input type="number" name="total_usd" value="{{ old('total_usd') }}" step="0.01" min="0" required readonly
                                        class="form-input !bg-primary/5 !h-12 !text-sm !font-black rounded-xl border-primary/20 text-primary cursor-not-allowed">
                                    <div class="absolute inset-y-0 right-3 flex items-center text-[0.65rem] font-black text-primary uppercase opacity-50">Auto</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-white/5 grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                            <div class="space-y-2">
                                <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Situación del Pago *</label>
                                <select name="estado" required class="form-input !bg-surface !h-12 !text-xs !font-bold rounded-xl border-white/5">
                                    @foreach(['PENDIENTE' => '⌛ Pendiente de Pago', 'APROBADA' => '✅ Aprobada (Cta/Cte)', 'PAGADA' => '🚀 Pagada (Contado)'] as $val => $lbl)
                                        <option value="{{ $val }}" @selected(old('estado', 'PAGADA') == $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="p-3 bg-red-500/5 rounded-2xl border border-red-500/10 flex items-center justify-between">
                                <span class="text-[0.55rem] font-black text-red-400 uppercase tracking-widest">Total proyectado:</span>
                                <span class="text-xs font-black text-white" id="total_footer_display">$ ...</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sección 4: Adjuntos y Notas --}}
                <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5">
                    <div class="erp-card-header !bg-transparent border-b border-white/5">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Documentación y Notas</h2>
                    </div>
                    <div class="erp-card-body p-6 space-y-6">
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest ml-1">Observaciones Internas</label>
                            <textarea name="descripcion" rows="3" class="form-input !bg-surface !text-xs font-bold rounded-xl border-white/5" placeholder="Agregue contexto adicional sobre este egreso...">{{ old('descripcion') }}</textarea>
                        </div>

                        <div class="space-y-4 pt-4 border-t border-white/5">
                             <div class="flex items-center justify-between">
                                <h3 class="text-[0.65rem] font-black text-primary uppercase tracking-widest">Soporte Documental</h3>
                                <button type="button" onclick="toggleDocZone()" class="text-[0.55rem] font-black text-muted-foreground hover:text-white uppercase transition-all">+ Gestionar Adjuntos</button>
                             </div>

                             <div id="doc-create-zone" style="display:none" class="space-y-4">
                                <div id="drop-area-create" onclick="document.getElementById('doc-files-create').click()"
                                    class="h-32 rounded-2xl border-2 border-dashed border-white/10 hover:border-primary/40 hover:bg-primary/5 transition-all flex flex-col items-center justify-center cursor-pointer group">
                                    <svg class="w-8 h-8 text-muted-foreground/30 group-hover:text-primary/60 transition-colors mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                    </svg>
                                    <span class="text-[0.6rem] font-black text-muted-foreground group-hover:text-primary transition-colors uppercase tracking-widest">Arrastre archivos o haga clic</span>
                                    <input type="file" id="doc-files-create" name="documentos[]" multiple class="hidden" onchange="updateDocList()">
                                </div>
                                <div id="doc-list-create" style="display:none" class="bg-surface2/50 rounded-2xl p-4 border border-white/5 space-y-2">
                                     <div class="text-[0.55rem] font-black text-muted-foreground uppercase mb-2">Archivos Preparados:</div>
                                     <div id="doc-names-create" class="space-y-2"></div>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="flex items-center justify-end gap-3 pt-6 pb-12">
                    <a href="{{ route('facturas.index') }}" class="btn btn-ghost px-8 rounded-xl font-black text-[0.65rem] uppercase tracking-widest border border-white/5">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-10 py-4 rounded-xl shadow-lg shadow-primary/20 font-black text-[0.7rem] uppercase tracking-widest border-b-2 border-primary-hover active:translate-y-0.5 transition-all">
                        💾 Registrar Transacción
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Modal: Registro de Proveedor Express --}}
    <div x-data="{ open: false }" @open-modal-prov.window="open = true" class="fixed inset-0 z-[100] flex items-center justify-center p-4 transition-all" x-show="open" x-cloak>
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="open = false"></div>
        <div class="relative w-full max-w-md bg-surface border border-white/10 rounded-3xl shadow-2xl overflow-hidden" x-show="open" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="p-6 border-b border-white/5">
                <h3 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    Proveedor Express
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="space-y-1">
                    <label class="text-[0.6rem] font-black text-muted-foreground uppercase ml-1">Razón Social *</label>
                    <input type="text" id="prov_rs" class="form-input !bg-surface h-11 text-xs font-bold rounded-xl border-white/5" placeholder="Nombre completo">
                </div>
                <div class="space-y-1">
                    <label class="text-[0.6rem] font-black text-muted-foreground uppercase ml-1">Tipo de Actividad</label>
                    <select id="prov_tipo" class="form-input !bg-surface h-11 text-xs font-bold rounded-xl border-white/5">
                        <option value="OTRO">Varios / Operativo</option>
                        <option value="DISTRIBUIDOR">Repuestos / Insumos</option>
                        <option value="SERVICIO">Servicio Técnico</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[0.6rem] font-black text-muted-foreground uppercase ml-1">Documento (RUC/CI)</label>
                    <input type="text" id="prov_ruc" class="form-input !bg-surface h-11 text-xs font-bold rounded-xl border-white/5" placeholder="Opcional">
                </div>
            </div>
            <div class="p-6 bg-surface3/30 border-t border-white/5 flex gap-2">
                <button type="button" @click="open = false" class="btn btn-ghost flex-1 h-11 rounded-xl text-[0.6rem] font-black tracking-widest uppercase">Cancelar</button>
                <button type="button" onclick="guardarProvAjax()" class="btn btn-primary flex-1 h-11 rounded-xl text-[0.6rem] font-black tracking-widest uppercase">Alta Rápida</button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleDestino() {
            const dest = document.getElementById('destino_sel').value;
            const vehCont = document.getElementById('vehiculo_container');
            const catCont = document.getElementById('categoria_vehiculo_container');
            const vehEl = document.getElementById('vehiculo_id');
            const cta = document.getElementById('cuenta_gasto');
            const esVehiculo = dest === 'VEHICULO';
            
            vehCont.style.display = esVehiculo ? '' : 'none';
            catCont.style.display = esVehiculo ? '' : 'none';
            vehEl.required = esVehiculo;
            cta.placeholder = esVehiculo ? 'Referencia (Ej: Flete, Pintura...)' : 'Ej: Alquiler, Sueldos, Energía...';
        }
        toggleDestino();

        let currentRates = { PYG: 1, BRL: 1 };
        async function fetchRates() {
            const fecha = document.querySelector('input[name="fecha_factura"]').value;
            try {
                const res = await fetch(`{{ route('cotizaciones.tasas-hoy') }}?fecha=${fecha}`);
                const data = await res.json();
                currentRates = data;
                calcularTotalUsd();
            } catch (e) { console.error('Error rates', e); }
        }

        function calcularTotalUsd() {
            const sub = parseFloat(document.querySelector('input[name="subtotal"]').value) || 0;
            const imp = parseFloat(document.querySelector('input[name="impuestos"]').value) || 0;
            const moneda = document.querySelector('input[name="moneda"]:checked')?.value || 'PYG';
            const total = sub + imp;
            let usd = total;

            if (moneda === 'PYG') usd = total / (currentRates.PYG || 1);
            else if (moneda === 'BRL') usd = total / (currentRates.BRL || 1);

            document.querySelector('input[name="total_usd"]').value = usd.toFixed(2);
            document.getElementById('total_footer_display').innerText = '$ ' + total.toLocaleString('de-DE', {minimumFractionDigits: 2});
        }

        async function guardarProvAjax() {
            const rs = document.getElementById('prov_rs').value.trim();
            if (!rs) { alert('Razón social requerida'); return; }
            try {
                const res = await fetch("{{ route('proveedores.store') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ 
                        razon_social: rs, 
                        tipo: document.getElementById('prov_tipo').value, 
                        ruc_rut_nit: document.getElementById('prov_ruc').value,
                        pais: 'PY', moneda_principal: 'PYG' 
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    const sel = document.getElementById('proveedor_sel');
                    const opt = new Option(data.proveedor.razon_social, data.proveedor.id, true, true);
                    sel.add(opt);
                    window.dispatchEvent(new CustomEvent('close-modal-prov'));
                    document.getElementById('prov_rs').value = '';
                } else { alert('Error al guardar'); }
            } catch (e) { alert('Error de red'); }
        }

        function toggleDocZone() {
            const zone = document.getElementById('doc-create-zone');
            zone.style.display = zone.style.display === 'none' ? 'block' : 'none';
        }

        function updateDocList() {
            const input = document.getElementById('doc-files-create');
            const list = document.getElementById('doc-list-create');
            const names = document.getElementById('doc-names-create');
            if (input.files.length > 0) {
                list.style.display = 'block';
                let html = '';
                for (let i = 0; i < input.files.length; i++) {
                    const f = input.files[i];
                    const sz = f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
                    html += `<div class="p-2.5 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <svg class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32"/></svg>
                            <span class="text-[0.65rem] font-bold text-white truncate">${f.name}</span>
                        </div>
                        <span class="text-[0.55rem] font-black text-muted-foreground uppercase">${sz}</span>
                    </div>`;
                }
                names.innerHTML = html;
            } else { list.style.display = 'none'; }
        }
        fetchRates();
    </script>
    @endpush
@endsection
