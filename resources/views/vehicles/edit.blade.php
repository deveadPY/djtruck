@extends('layouts.app')
@section('title', 'Editar Vehículo')
@section('page-title', 'Editar Vehículo')

@section('content')
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver
        </a>
    </div>

    <div class="erp-card">
        <div class="erp-card-header">
            <h2>{{ $vehiculo->marca }} {{ $vehiculo->modelo }} — Chasis: {{ $vehiculo->numero_chasis }}</h2>
        </div>
        <div class="erp-card-body">
            <form method="POST" action="{{ route('vehicles.update', $vehiculo->id) }}"
                enctype="multipart/form-data"
                data-confirm="Confirmar actualización de vehículo">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Marca *</label>
                        <input type="text" name="marca" value="{{ old('marca', $vehiculo->marca) }}" required
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Modelo *</label>
                        <input type="text" name="modelo" value="{{ old('modelo', $vehiculo->modelo) }}" required
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Año *</label>
                        <input type="number" name="anio" value="{{ old('anio', $vehiculo->anio) }}" min="1980" max="2030"
                            required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" value="{{ old('color', $vehiculo->color) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de vehículo *</label>
                        <select name="tipo_vehiculo" required class="form-input">
                            @foreach(['CAMION_RIGIDO' => 'Camión Rígido', 'CAMION_TRACTO' => 'Camión Tracto', 'SEMI_REMOLQUE' => 'Semi Remolque', 'FURGON' => 'Furgón', 'VOLQUETE' => 'Volquete', 'CISTERNA' => 'Cisterna', 'OTRO' => 'Otro'] as $val => $label)
                                <option value="{{ $val }}" {{ old('tipo_vehiculo', $vehiculo->tipo_vehiculo) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado *</label>
                        <select name="estado" required class="form-input">
                            @foreach(['EN_TRANSITO', 'EN_ADUANA', 'EN_PREPARACION', 'DISPONIBLE', 'RESERVADO', 'TOMA', 'BAJA'] as $val)
                                <option value="{{ $val }}" {{ old('estado', $vehiculo->estado) == $val ? 'selected' : '' }}>
                                    {{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kilometraje</label>
                        <input type="number" name="kilometraje" value="{{ old('kilometraje', $vehiculo->kilometraje) }}"
                            min="0" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor_id" class="form-input">
                            <option value="">— Sin proveedor —</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ old('proveedor_id', $vehiculo->proveedor_id) == $p->id ? 'selected' : '' }}>{{ $p->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Moneda de costo *</label>
                        <select name="moneda_costo" required class="form-input">
                            @foreach(['USD', 'PYG', 'BRL'] as $m)
                                <option value="{{ $m }}" {{ old('moneda_costo', $vehiculo->moneda_costo) == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Costo en moneda origen *</label>
                        <input type="number" name="costo_origen_moneda"
                            value="{{ old('costo_origen_moneda', $vehiculo->costo_origen_moneda) }}" step="0.01" min="0"
                            required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Costo en USD *</label>
                        <input type="number" name="costo_origen_usd"
                            value="{{ old('costo_origen_usd', $vehiculo->costo_origen_usd) }}" step="0.01" min="0" required
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Precio venta contado (USD)</label>
                        <input type="number" name="precio_contado_usd"
                            value="{{ old('precio_contado_usd', $vehiculo->precio_contado_usd) }}" step="0.01"
                            min="0" class="form-input" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Precio venta en cuotas (USD)</label>
                        <input type="number" name="precio_cuotas_usd"
                            value="{{ old('precio_cuotas_usd', $vehiculo->precio_cuotas_usd) }}" step="0.01"
                            min="0" class="form-input" placeholder="0.00">
                    </div>
                </div>
                {{-- Especificaciones técnicas para el catálogo --}}
                <div class="mt-6 pt-5 border-t" style="border-color: var(--border);">
                    <h3 class="form-label mb-3 flex items-center gap-2 text-base font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Especificaciones técnicas <span class="text-xs font-normal opacity-50">(para el catálogo web)</span>
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Motor</label>
                            <input type="text" name="motor_descripcion"
                                value="{{ old('motor_descripcion', $vehiculo->motor_descripcion) }}"
                                placeholder="Ej: DC13 500 CV Euro 6" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Potencia (HP)</label>
                            <input type="number" name="potencia_hp"
                                value="{{ old('potencia_hp', $vehiculo->potencia_hp) }}"
                                min="0" max="9999" placeholder="500" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Par motor (Nm)</label>
                            <input type="number" name="par_nm"
                                value="{{ old('par_nm', $vehiculo->par_nm) }}"
                                min="0" max="99999" placeholder="2550" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tracción</label>
                            <select name="tipo_traccion" class="form-input">
                                <option value="">— Sin especificar —</option>
                                @foreach(['4x2', '4x4', '6x2', '6x4', '8x4', '6x6', '8x8'] as $t)
                                    <option value="{{ $t }}" {{ old('tipo_traccion', $vehiculo->tipo_traccion) == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Transmisión</label>
                            <select name="tipo_transmision" class="form-input">
                                <option value="">— Sin especificar —</option>
                                <option value="MANUAL" {{ old('tipo_transmision', $vehiculo->tipo_transmision) == 'MANUAL' ? 'selected' : '' }}>Manual</option>
                                <option value="AUTOMATICA" {{ old('tipo_transmision', $vehiculo->tipo_transmision) == 'AUTOMATICA' ? 'selected' : '' }}>Automática</option>
                                <option value="AUTOMATIZADA" {{ old('tipo_transmision', $vehiculo->tipo_transmision) == 'AUTOMATIZADA' ? 'selected' : '' }}>Automatizada (AMT)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cabina</label>
                            <input type="text" name="cabina"
                                value="{{ old('cabina', $vehiculo->cabina) }}"
                                placeholder="Ej: Highline, Topline, Day Cab" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Norma de emisión</label>
                            <input type="text" name="norma_euro"
                                value="{{ old('norma_euro', $vehiculo->norma_euro) }}"
                                placeholder="Ej: Euro 6, Euro 5" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Peso bruto vehicular (t)</label>
                            <input type="number" name="peso_bruto_t"
                                value="{{ old('peso_bruto_t', $vehiculo->peso_bruto_t) }}"
                                min="0" max="999" step="0.01" placeholder="48.00" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Depósito combustible (L)</label>
                            <input type="number" name="deposito_litros"
                                value="{{ old('deposito_litros', $vehiculo->deposito_litros) }}"
                                min="0" max="9999" placeholder="600" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Neumáticos</label>
                            <input type="text" name="neumaticos"
                                value="{{ old('neumaticos', $vehiculo->neumaticos) }}"
                                placeholder="Ej: 295/60 R22.5" class="form-input">
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <label class="form-label">Descripción para el sitio web</label>
                        <textarea name="descripcion_publica" rows="3" maxlength="2000"
                            placeholder="Descripción comercial que verán los visitantes del sitio..."
                            class="form-input">{{ old('descripcion_publica', $vehiculo->descripcion_publica) }}</textarea>
                    </div>

                    <div class="form-group mt-4">
                        <label class="form-label mb-2">Equipamiento / Características destacadas</label>
                        <div id="equipamiento-tags" class="flex flex-wrap gap-2 mb-2">
                            @foreach(old('equipamiento', $vehiculo->equipamiento ?? []) as $item)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs border" style="border-color:var(--border);background:var(--bg-card)">
                                    {{ $item }}
                                    <button type="button" onclick="removeTag(this)" class="opacity-60 hover:opacity-100">&times;</button>
                                    <input type="hidden" name="equipamiento[]" value="{{ $item }}">
                                </span>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <input type="text" id="equipamiento-input" placeholder="Ej: ABS, Aire acondicionado, GPS…"
                                class="form-input flex-1" onkeydown="if(event.key==='Enter'){event.preventDefault();addTag()}">
                            <button type="button" onclick="addTag()" class="btn btn-ghost text-sm">Agregar</button>
                        </div>
                    </div>
                </div>

                {{-- Publicación en el sitio web --}}
                <div class="mt-6 pt-5 border-t" style="border-color: var(--border);">
                    <h3 class="form-label mb-3 flex items-center gap-2 text-base font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253M2.99 9.337A8.993 8.993 0 0 0 3 12c0 .778.1 1.533.284 2.253" />
                        </svg>
                        Publicación en el sitio web
                    </h3>
                    <div class="flex flex-col gap-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="publicar_en_web" value="0">
                            <input type="checkbox" name="publicar_en_web" value="1"
                                {{ old('publicar_en_web', $vehiculo->publicar_en_web) ? 'checked' : '' }}
                                class="w-4 h-4 rounded" style="accent-color:var(--primary)">
                            <span class="text-sm">Publicar este vehículo en el sitio web</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="mostrar_precio" value="0">
                            <input type="checkbox" name="mostrar_precio" value="1"
                                {{ old('mostrar_precio', $vehiculo->mostrar_precio ?? true) ? 'checked' : '' }}
                                class="w-4 h-4 rounded" style="accent-color:var(--primary)">
                            <span class="text-sm">Mostrar precio al público</span>
                        </label>
                    </div>
                </div>

                {{-- ═══════════════════════ GESTIÓN DE IMÁGENES ═══════════════════════ --}}
                <div class="mt-6 pt-5 border-t" style="border-color: var(--border);">
                    <h3 class="form-label mb-3 flex items-center gap-2 text-base font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        Imágenes del vehículo
                        <span class="text-xs font-normal opacity-50">({{ count($imagenes) }} {{ count($imagenes) === 1 ? 'imagen' : 'imágenes' }})</span>
                    </h3>

                    {{-- Imágenes existentes --}}
                    @if(count($imagenes) > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-4">
                            @foreach($imagenes as $img)
                                <div class="relative group rounded-lg overflow-hidden border-2 transition-all"
                                     style="border-color: {{ $img->es_portada ? 'var(--primary)' : 'var(--border)' }};">
                                    <img src="{{ asset($img->ruta) }}"
                                         alt="{{ $img->nombre_original }}"
                                         class="w-full h-32 object-cover">

                                    {{-- Badge portada --}}
                                    @if($img->es_portada)
                                        <div class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-md text-[0.6rem] font-black uppercase tracking-wider"
                                             style="background: var(--primary); color: white;">
                                            ★ Portada
                                        </div>
                                    @endif

                                    {{-- Overlay de acciones --}}
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                        @if(!$img->es_portada)
                                            <form method="POST"
                                                  action="{{ route('vehicles.imagenes.portada', [$vehiculo->id, $img->id]) }}"
                                                  onclick="event.stopPropagation()">
                                                @csrf
                                                <button type="submit"
                                                        title="Marcar como portada"
                                                        class="p-2 rounded-full bg-white/20 hover:bg-yellow-500 text-white transition-colors">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST"
                                              action="{{ route('vehicles.imagenes.destroy', [$vehiculo->id, $img->id]) }}"
                                              onsubmit="return confirm('¿Eliminar esta imagen? Esta acción se puede revertir desde la base de datos.')"
                                              onclick="event.stopPropagation()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    title="Eliminar imagen"
                                                    class="p-2 rounded-full bg-white/20 hover:bg-red-500 text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm opacity-60 mb-4 italic">Este vehículo aún no tiene imágenes.</div>
                    @endif

                    {{-- Subida de nuevas --}}
                    <div class="form-group">
                        <label class="form-label">Agregar más imágenes</label>
                        <input type="file"
                               name="imagenes[]"
                               multiple
                               accept="image/jpeg,image/jpg,image/png,image/webp"
                               class="form-input">
                        <p class="text-xs opacity-60 mt-1">
                            Formatos permitidos: JPG, PNG, WEBP. Puedes seleccionar varias a la vez.
                            @if(count($imagenes) === 0)
                                La primera imagen se establecerá automáticamente como portada.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex gap-3 justify-end pt-5 mt-4 border-t" style="border-color: var(--border);">
                    <a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let currentRates = { PYG: 1, BRL: 1 };

            async function fetchRates() {
                try {
                    const fecha = new Date().toISOString().split('T')[0];
                    const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${fecha}`);
                    const data = await res.json();
                    currentRates = data;
                    calcularTotalUsd();
                } catch (e) { console.error('Error fetching rates', e); }
            }

            function calcularTotalUsd() {
                const costoMoneda = parseFloat(document.querySelector('input[name="costo_origen_moneda"]').value) || 0;
                const moneda = document.querySelector('select[name="moneda_costo"]').value;
                let costoUsd = costoMoneda;

                if (moneda === 'PYG') {
                    const rate = currentRates.PYG || 1;
                    costoUsd = costoMoneda / rate;
                } else if (moneda === 'BRL') {
                    const rate = currentRates.BRL || 1;
                    costoUsd = costoMoneda / rate;
                }

                document.querySelector('input[name="costo_origen_usd"]').value = costoUsd.toFixed(2);
            }

            document.querySelector('select[name="moneda_costo"]').addEventListener('change', calcularTotalUsd);
            document.querySelector('input[name="costo_origen_moneda"]').addEventListener('input', calcularTotalUsd);
            fetchRates();

            // ── Equipamiento Tags ──────────────────────────
            function addTag() {
                const input = document.getElementById('equipamiento-input');
                const val = input.value.trim();
                if (!val) return;
                const container = document.getElementById('equipamiento-tags');
                const span = document.createElement('span');
                span.className = 'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs border';
                span.style = 'border-color:var(--border);background:var(--bg-card)';
                span.innerHTML = `${val} <button type="button" onclick="removeTag(this)" class="opacity-60 hover:opacity-100">&times;</button><input type="hidden" name="equipamiento[]" value="${val}">`;
                container.appendChild(span);
                input.value = '';
            }

            function removeTag(btn) {
                btn.closest('span').remove();
            }

            window.addTag    = addTag;
            window.removeTag = removeTag;
        </script>
    @endpush
@endsection