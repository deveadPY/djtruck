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
                        <input type="number" name="año" value="{{ old('año', date('Y')) }}" min="1980" max="2030" required
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
        </script>
    @endpush
@endsection