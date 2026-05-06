@extends('layouts.app')
@section('title', 'Nuevo Vehículo')
@section('page-title', '🚛 Registrar Vehículo')

@push('styles')
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .4rem
        }

        .form-group.full {
            grid-column: 1/-1
        }

        label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em
        }

        input,
        select,
        textarea {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .65rem .9rem;
            color: var(--text);
            font-family: inherit;
            font-size: .875rem;
            outline: none;
            width: 100%;
            transition: border-color .2s
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary)
        }

        .img-drop-zone {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }

        .img-drop-zone:hover,
        .img-drop-zone.dragover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, .05);
        }

        .img-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: .75rem;
            margin-top: 1rem;
        }

        .img-preview-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border);
            aspect-ratio: 4/3;
        }

        .img-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-preview-item .remove-img {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(0, 0, 0, .6);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <div
            style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
            {{ session('success') }}
    </div>@endif
    @if($errors->any())
        <div
            style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
            {{ $errors->first() }}
    </div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('vehicles.index') }}" class="btn btn-ghost">← Volver</a></div>

    <div class="card">
        <div class="card-header">
            <h2>Datos del vehículo</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('vehicles.store') }}" data-confirm="Confirmar registro de vehículo"
                enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Número de chasis *</label>
                        <input type="text" name="numero_chasis" value="{{ old('numero_chasis') }}"
                            placeholder="9BM384075PB295123" required maxlength="17">
                    </div>
                    <div class="form-group">
                        <label>Número de motor</label>
                        <input type="text" name="numero_motor" value="{{ old('numero_motor') }}" placeholder="Opcional">
                    </div>
                    <div class="form-group">
                        <label>Marca *</label>
                        <input type="text" name="marca" value="{{ old('marca') }}" placeholder="Mercedes-Benz" required>
                    </div>
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="modelo" value="{{ old('modelo') }}" placeholder="Actros 2651" required>
                    </div>
                    <div class="form-group">
                        <label>Año *</label>
                        <input type="number" name="año" value="{{ old('año', date('Y')) }}" min="1980" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color" value="{{ old('color') }}" placeholder="Blanco">
                    </div>
                    <div class="form-group">
                        <label>Tipo de vehículo *</label>
                        <select name="tipo_vehiculo" required>
                            @foreach(['CAMION_RIGIDO' => 'Camión Rígido', 'CAMION_TRACTO' => 'Camión Tracto', 'SEMI_REMOLQUE' => 'Semi Remolque', 'FURGON' => 'Furgón', 'VOLQUETE' => 'Volquete', 'CISTERNA' => 'Cisterna', 'OTRO' => 'Otro'] as $val => $label)
                                <option value="{{ $val }}" {{ old('tipo_vehiculo') == $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado *</label>
                        <select name="estado" required>
                            @foreach(['EN_TRANSITO' => 'En Tránsito', 'EN_ADUANA' => 'En Aduana', 'EN_PREPARACION' => 'En Preparación', 'DISPONIBLE' => 'Disponible', 'TOMA' => 'Tomado en canje'] as $val => $label)
                                <option value="{{ $val }}" {{ old('estado', 'DISPONIBLE') == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kilometraje</label>
                        <input type="number" name="kilometraje" value="{{ old('kilometraje', 0) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label>Proveedor</label>
                        <select name="proveedor_id">
                            <option value="">— Sin proveedor —</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ old('proveedor_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->razon_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Moneda de costo *</label>
                        <select name="moneda_costo" required id="moneda_sel">
                            <option value="USD" {{ old('moneda_costo', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="PYG" {{ old('moneda_costo') == 'PYG' ? 'selected' : '' }}>PYG</option>
                            <option value="BRL" {{ old('moneda_costo') == 'BRL' ? 'selected' : '' }}>BRL</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Costo en moneda origen *</label>
                        <input type="number" name="costo_origen_moneda" value="{{ old('costo_origen_moneda') }}" step="0.01"
                            min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Costo en USD *</label>
                        <input type="number" name="costo_origen_usd" value="{{ old('costo_origen_usd') }}" step="0.01"
                            min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Tasa de cambio compra</label>
                        <input type="number" name="tasa_cambio_compra" value="{{ old('tasa_cambio_compra', 1) }}"
                            step="0.0001" readonly style="background:var(--surface1); cursor:not-allowed;">
                    </div>
                </div>

                {{-- Zona de carga de imágenes --}}
                <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border)">
                    <label style="margin-bottom:.5rem;display:block">📸 Imágenes del vehículo</label>
                    <div class="img-drop-zone" id="dropZone" onclick="document.getElementById('imageInput').click()">
                        <div style="font-size:2rem;margin-bottom:.5rem">📷</div>
                        <p style="margin:0;font-size:.85rem">Arrastra imágenes aquí o <strong
                                style="color:var(--primary)">haz clic para buscar</strong></p>
                        <p style="margin:.25rem 0 0;font-size:.75rem">JPG, PNG, WEBP — Máximo 10 imágenes</p>
                    </div>
                    <input type="file" id="imageInput" name="imagenes[]" multiple accept="image/*" style="display:none">
                    <div class="img-preview-grid" id="previewGrid"></div>
                </div>

                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar vehículo</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let currentRates = { PYG: 1, BRL: 1 };

            async function fetchRates() {
                try {
                    // Obtenemos la fecha de hoy para vehículos de forma predeterminada
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

            // Fetch initial rates on load
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

            // Expose removeFile globally
            window.removeFile = removeFile;
        </script>
    @endpush
@endsection