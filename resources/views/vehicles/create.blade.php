@extends('layouts.app')
@section('title', 'Nuevo Vehículo')
@section('page-title', 'Registrar Vehículo')

@push('styles')
    <style>
        .img-drop-zone {
            @apply border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200;
            border-color: var(--border);
            color: var(--text-muted);
        }

        .img-drop-zone:hover,
        .img-drop-zone.dragover {
            @apply border-primary;
            background: rgba(108, 99, 255, .05);
        }

        .img-preview-item {
            @apply relative rounded-xl overflow-hidden border aspect-[4/3];
            border-color: var(--border);
        }

        .img-preview-item img {
            @apply w-full h-full object-cover;
        }

        .img-preview-item .remove-img {
            @apply absolute top-1 right-1 w-6 h-6 rounded-full border-none cursor-pointer flex items-center justify-center text-white text-xs;
            background: rgba(0, 0, 0, .6);
        }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver
        </a>
    </div>

    <div class="erp-card">
        <div class="erp-card-header">
            <h2>Datos del vehículo</h2>
        </div>
        <div class="erp-card-body">
            <form method="POST" action="{{ route('vehicles.store') }}" data-confirm="Confirmar registro de vehículo"
                enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Número de chasis *</label>
                        <input type="text" name="numero_chasis" value="{{ old('numero_chasis') }}"
                            placeholder="9BM384075PB295123" required maxlength="17" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número de motor</label>
                        <input type="text" name="numero_motor" value="{{ old('numero_motor') }}" placeholder="Opcional"
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Marca *</label>
                        <input type="text" name="marca" value="{{ old('marca') }}" placeholder="Mercedes-Benz" required
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Modelo *</label>
                        <input type="text" name="modelo" value="{{ old('modelo') }}" placeholder="Actros 2651" required
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Año *</label>
                        <input type="number" name="anio" value="{{ old('anio', date('Y')) }}" min="1980" max="2030" required
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" value="{{ old('color') }}" placeholder="Blanco" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de vehículo *</label>
                        <select name="tipo_vehiculo" required class="form-input">
                            @foreach(['CAMION_RIGIDO' => 'Camión Rígido', 'CAMION_TRACTO' => 'Camión Tracto', 'SEMI_REMOLQUE' => 'Semi Remolque', 'FURGON' => 'Furgón', 'VOLQUETE' => 'Volquete', 'CISTERNA' => 'Cisterna', 'OTRO' => 'Otro'] as $val => $label)
                                <option value="{{ $val }}" {{ old('tipo_vehiculo') == $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado *</label>
                        <select name="estado" required class="form-input">
                            @foreach(['EN_TRANSITO' => 'En Tránsito', 'EN_ADUANA' => 'En Aduana', 'EN_PREPARACION' => 'En Preparación', 'DISPONIBLE' => 'Disponible', 'TOMA' => 'Tomado en canje'] as $val => $label)
                                <option value="{{ $val }}" {{ old('estado', 'DISPONIBLE') == $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kilometraje</label>
                        <input type="number" name="kilometraje" value="{{ old('kilometraje', 0) }}" min="0"
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor_id" class="form-input">
                            <option value="">— Sin proveedor —</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ old('proveedor_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Moneda de costo *</label>
                        <select name="moneda_costo" required id="moneda_sel" class="form-input">
                            <option value="USD" {{ old('moneda_costo', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="PYG" {{ old('moneda_costo') == 'PYG' ? 'selected' : '' }}>PYG</option>
                            <option value="BRL" {{ old('moneda_costo') == 'BRL' ? 'selected' : '' }}>BRL</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Costo en moneda origen *</label>
                        <input type="number" name="costo_origen_moneda" value="{{ old('costo_origen_moneda') }}" step="0.01"
                            min="0" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Costo en USD *</label>
                        <input type="number" name="costo_origen_usd" value="{{ old('costo_origen_usd') }}" step="0.01"
                            min="0" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tasa de cambio compra</label>
                        <input type="number" name="tasa_cambio_compra" value="{{ old('tasa_cambio_compra', 1) }}"
                            step="0.0001" readonly class="form-input cursor-not-allowed opacity-60">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Precio venta contado (USD)</label>
                        <input type="number" name="precio_contado_usd" value="{{ old('precio_contado_usd') }}"
                            step="0.01" min="0" class="form-input" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Precio venta en cuotas (USD)</label>
                        <input type="number" name="precio_cuotas_usd" value="{{ old('precio_cuotas_usd') }}"
                            step="0.01" min="0" class="form-input" placeholder="0.00">
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
                            <input type="text" name="motor_descripcion" value="{{ old('motor_descripcion') }}"
                                placeholder="Ej: DC13 500 CV Euro 6" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Potencia (HP)</label>
                            <input type="number" name="potencia_hp" value="{{ old('potencia_hp') }}"
                                min="0" max="9999" placeholder="500" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Par motor (Nm)</label>
                            <input type="number" name="par_nm" value="{{ old('par_nm') }}"
                                min="0" max="99999" placeholder="2550" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tracción</label>
                            <select name="tipo_traccion" class="form-input">
                                <option value="">— Sin especificar —</option>
                                @foreach(['4x2', '4x4', '6x2', '6x4', '8x4', '6x6', '8x8'] as $t)
                                    <option value="{{ $t }}" {{ old('tipo_traccion') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Transmisión</label>
                            <select name="tipo_transmision" class="form-input">
                                <option value="">— Sin especificar —</option>
                                <option value="MANUAL" {{ old('tipo_transmision') == 'MANUAL' ? 'selected' : '' }}>Manual</option>
                                <option value="AUTOMATICA" {{ old('tipo_transmision') == 'AUTOMATICA' ? 'selected' : '' }}>Automática</option>
                                <option value="AUTOMATIZADA" {{ old('tipo_transmision') == 'AUTOMATIZADA' ? 'selected' : '' }}>Automatizada (AMT)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cabina</label>
                            <input type="text" name="cabina" value="{{ old('cabina') }}"
                                placeholder="Ej: Highline, Topline, Day Cab" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Norma de emisión</label>
                            <input type="text" name="norma_euro" value="{{ old('norma_euro') }}"
                                placeholder="Ej: Euro 6, Euro 5" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Peso bruto vehicular (t)</label>
                            <input type="number" name="peso_bruto_t" value="{{ old('peso_bruto_t') }}"
                                min="0" max="999" step="0.01" placeholder="48.00" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Depósito combustible (L)</label>
                            <input type="number" name="deposito_litros" value="{{ old('deposito_litros') }}"
                                min="0" max="9999" placeholder="600" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Neumáticos</label>
                            <input type="text" name="neumaticos" value="{{ old('neumaticos') }}"
                                placeholder="Ej: 295/60 R22.5" class="form-input">
                        </div>
                    </div>

                    {{-- Descripción pública --}}
                    <div class="form-group mt-4">
                        <label class="form-label">Descripción para el sitio web</label>
                        <textarea name="descripcion_publica" rows="3" maxlength="2000"
                            placeholder="Descripción comercial que verán los visitantes del sitio..."
                            class="form-input">{{ old('descripcion_publica') }}</textarea>
                    </div>

                    {{-- Equipamiento --}}
                    <div class="form-group mt-4">
                        <label class="form-label mb-2">Equipamiento / Características destacadas</label>
                        <div id="equipamiento-tags" class="flex flex-wrap gap-2 mb-2">
                            @foreach(old('equipamiento', []) as $item)
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
                                {{ old('publicar_en_web') ? 'checked' : '' }}
                                class="w-4 h-4 rounded" style="accent-color:var(--primary)">
                            <span class="text-sm">Publicar este vehículo en el sitio web</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="mostrar_precio" value="0">
                            <input type="checkbox" name="mostrar_precio" value="1"
                                {{ old('mostrar_precio', true) ? 'checked' : '' }}
                                class="w-4 h-4 rounded" style="accent-color:var(--primary)">
                            <span class="text-sm">Mostrar precio al público</span>
                        </label>
                    </div>
                </div>

                {{-- Zona de carga de imágenes --}}
                <div class="mt-6 pt-5 border-t" style="border-color: var(--border);">
                    <label class="form-label mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                        </svg>
                        Imágenes del vehículo
                    </label>
                    <div class="img-drop-zone" id="dropZone" onclick="document.getElementById('imageInput').click()">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <p class="text-sm">Arrastra imágenes aquí o <strong class="text-primary">haz clic para
                                buscar</strong></p>
                        <p class="text-xs mt-1">JPG, PNG, WEBP — Máximo 10 imágenes</p>
                    </div>
                    <input type="file" id="imageInput" name="imagenes[]" multiple accept="image/*" class="hidden">
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mt-4" id="previewGrid"></div>
                </div>

                <div class="flex gap-3 justify-end pt-5 mt-4 border-t" style="border-color: var(--border);">
                    <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Guardar vehículo
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
                let tasaUsada = null;

                if (moneda === 'PYG') {
                    const rate = parseFloat(currentRates.PYG) || 1;
                    costoUsd = costoMoneda / rate;
                    tasaUsada = rate;
                } else if (moneda === 'BRL') {
                    const rate = parseFloat(currentRates.BRL) || 1;
                    costoUsd = costoMoneda / rate;
                    tasaUsada = rate;
                }

                document.querySelector('input[name="costo_origen_usd"]').value = costoUsd.toFixed(2);
                if (tasaUsada) {
                    document.querySelector('input[name="tasa_cambio_compra"]').value = tasaUsada.toFixed(2);
                } else {
                    document.querySelector('input[name="tasa_cambio_compra"]').value = '1';
                }
            }

            document.querySelector('select[name="moneda_costo"]').addEventListener('change', calcularTotalUsd);
            document.querySelector('input[name="costo_origen_moneda"]').addEventListener('input', calcularTotalUsd);
            fetchRates();

            // ── Image Upload Logic ──────────────────────────
            const dropZone = document.getElementById('dropZone');
            const imageInput = document.getElementById('imageInput');
            const previewGrid = document.getElementById('previewGrid');
            let filesArray = [];

            dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
            dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('dragover'); });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                handleFiles(e.dataTransfer.files);
            });

            imageInput.addEventListener('change', (e) => { handleFiles(e.target.files); });

            function handleFiles(fileList) {
                for (const file of fileList) {
                    if (!file.type.startsWith('image/')) continue;
                    if (filesArray.length >= 10) break;
                    filesArray.push(file);
                }
                updatePreview();
                syncInputFiles();
            }

            function removeFile(index) {
                filesArray.splice(index, 1);
                updatePreview();
                syncInputFiles();
            }

            function updatePreview() {
                previewGrid.innerHTML = '';
                filesArray.forEach((file, i) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const div = document.createElement('div');
                        div.className = 'img-preview-item';
                        div.innerHTML = `<img src="${e.target.result}"><button type="button" class="remove-img" onclick="removeFile(${i})">&times;</button>`;
                        previewGrid.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }

            function syncInputFiles() {
                const dt = new DataTransfer();
                filesArray.forEach(f => dt.items.add(f));
                imageInput.files = dt.files;
            }

            window.removeFile = removeFile;

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