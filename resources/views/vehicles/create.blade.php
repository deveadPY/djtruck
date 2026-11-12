@extends('layouts.app')
@section('title', 'Nuevo Vehículo')
@section('page-title', 'Registrar Vehículo')

@push('styles')
    <style>
        /* CSS plano: la directiva apply no funciona en un <style> normal con el CDN de Tailwind. */
        .alta-seccion + .alta-seccion { margin-top: 1.25rem; }
        .alta-paso {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.5rem; height: 1.5rem; border-radius: 9999px; font-size: .75rem; font-weight: 700;
            background: color-mix(in srgb, var(--primary) 14%, transparent); color: var(--primary);
        }
        .alta-ayuda { font-size: .75rem; color: var(--text-muted); margin-top: .25rem; }
        .form-input::placeholder { color: var(--text-muted); opacity: .55; font-style: italic; }
        .form-input[aria-invalid="true"] { border-color: var(--danger); }
        .alta-opcional > summary { list-style: none; cursor: pointer; }
        .alta-opcional > summary::-webkit-details-marker { display: none; }
        .alta-opcional > summary .alta-chevron { transition: transform 200ms cubic-bezier(.16, 1, .3, 1); }
        .alta-opcional[open] > summary .alta-chevron { transform: rotate(180deg); }
        .alta-acciones {
            position: sticky; bottom: 0; z-index: 20; margin-top: 1.25rem;
            background: color-mix(in srgb, var(--surface) 92%, transparent);
            backdrop-filter: blur(6px); border-top: 1px solid var(--border);
        }
        .img-drop-zone {
            display: block; width: 100%; border: 2px dashed var(--border); border-radius: .75rem;
            padding: 1.75rem; text-align: center; cursor: pointer; color: var(--text-muted);
            background: transparent; transition: border-color 200ms, background-color 200ms;
        }
        .img-drop-zone:hover, .img-drop-zone.dragover, .img-drop-zone:focus-visible {
            border-color: var(--primary); background: color-mix(in srgb, var(--primary) 5%, transparent);
        }
        .img-preview-item { position: relative; border-radius: .75rem; overflow: hidden; border: 1px solid var(--border); aspect-ratio: 4 / 3; }
        .img-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .img-preview-item .remove-img {
            position: absolute; top: .375rem; right: .375rem; width: 2rem; height: 2rem; border-radius: 9999px;
            border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #fff; background: rgba(0, 0, 0, .65);
        }
        .img-preview-item .portada {
            position: absolute; left: .375rem; bottom: .375rem; font-size: .65rem; font-weight: 600;
            padding: .125rem .5rem; border-radius: 9999px; color: #fff; background: rgba(0, 0, 0, .65);
        }
    </style>
@endpush

@php
    // Atributos de accesibilidad para campos con error de validación
    $inv = fn (string $campo) => $errors->has($campo) ? 'aria-invalid=true aria-describedby=' . $campo . '-error' : '';
    $tecnicos = ['motor_descripcion', 'potencia_hp', 'par_nm', 'tipo_traccion', 'tipo_transmision', 'cabina', 'norma_euro', 'peso_bruto_t', 'deposito_litros', 'neumaticos', 'descripcion_publica', 'equipamiento'];
    $abrirTecnicos = collect($tecnicos)->contains(fn ($c) => filled(old($c)) || $errors->has($c));
    $etiquetas = [
        'numero_chasis' => 'Número de chasis', 'marca' => 'Marca', 'modelo' => 'Modelo', 'anio' => 'Año',
        'tipo_vehiculo' => 'Tipo de vehículo', 'estado' => 'Estado', 'costo_origen_moneda' => 'Costo',
        'costo_origen_usd' => 'Costo', 'tasa_cambio_compra' => 'Cotización', 'imagenes' => 'Imágenes',
    ];
@endphp

@section('content')
    <div class="mb-4">
        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a vehículos
        </a>
    </div>

    @if($errors->any())
        <div class="flash-error" role="alert" tabindex="-1" id="resumenErrores">
            <p class="font-semibold mb-1">Revisá {{ $errors->count() === 1 ? 'este dato' : 'estos ' . $errors->count() . ' datos' }} antes de guardar:</p>
            <ul>
                @foreach($errors->keys() as $campo)
                    @php $base = \Illuminate\Support\Str::before($campo, '.'); @endphp
                    <li><a href="#{{ $base === 'costo_origen_usd' ? 'costo_origen_moneda' : $base }}" class="underline">{{ $etiquetas[$base] ?? ucfirst(str_replace('_', ' ', $base)) }}</a>: {{ $errors->first($campo) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('vehicles.store') }}" data-confirm="Confirmar registro de vehículo"
        enctype="multipart/form-data">
        @csrf

        {{-- 1. Identificación --}}
        <section class="erp-card alta-seccion" aria-labelledby="sec-identificacion">
            <div class="erp-card-header !justify-start gap-3">
                <span class="alta-paso" aria-hidden="true">1</span>
                <h2 id="sec-identificacion">Identificación del camión</h2>
            </div>
            <div class="erp-card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="numero_chasis">Número de chasis (VIN) *</label>
                        <input type="text" id="numero_chasis" name="numero_chasis" value="{{ old('numero_chasis') }}"
                            required maxlength="17" autocomplete="off" spellcheck="false" autofocus
                            class="form-input tracking-wider" placeholder="Ej: 9BM384075PB295123" {!! $inv('numero_chasis') !!}>
                        <div class="alta-ayuda flex justify-between gap-2">
                            <span>Está en la placa del chasis o en los documentos.</span>
                            <span id="chasisContador" aria-live="polite">0/17</span>
                        </div>
                        <x-campo-error name="numero_chasis" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tipo_vehiculo">Tipo de vehículo *</label>
                        <select id="tipo_vehiculo" name="tipo_vehiculo" required class="form-input" {!! $inv('tipo_vehiculo') !!}>
                            @foreach(['CAMION_TRACTO' => 'Camión tracto', 'CAMION_RIGIDO' => 'Camión rígido', 'SEMI_REMOLQUE' => 'Semirremolque', 'FURGON' => 'Furgón', 'VOLQUETE' => 'Volquete', 'CISTERNA' => 'Cisterna', 'OTRO' => 'Otro'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('tipo_vehiculo', 'CAMION_TRACTO') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-campo-error name="tipo_vehiculo" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="marca">Marca *</label>
                        <input type="text" id="marca" name="marca" value="{{ old('marca') }}" required maxlength="80"
                            list="marcasExistentes" autocomplete="off" class="form-input" placeholder="Ej: Scania" {!! $inv('marca') !!}>
                        <datalist id="marcasExistentes">
                            @foreach($marcas as $m)
                                <option value="{{ $m }}">
                            @endforeach
                        </datalist>
                        <x-campo-error name="marca" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modelo">Modelo *</label>
                        <input type="text" id="modelo" name="modelo" value="{{ old('modelo') }}" required maxlength="80"
                            class="form-input" placeholder="Ej: R450" {!! $inv('modelo') !!}>
                        <x-campo-error name="modelo" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="anio">Año *</label>
                        <input type="number" id="anio" name="anio" value="{{ old('anio', date('Y')) }}" min="1980" max="{{ date('Y') + 1 }}"
                            required inputmode="numeric" class="form-input" {!! $inv('anio') !!}>
                        <x-campo-error name="anio" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="kilometraje">Kilometraje</label>
                        <input type="number" id="kilometraje" name="kilometraje" value="{{ old('kilometraje') }}" min="0" max="9999999"
                            inputmode="numeric" class="form-input" placeholder="Ej: 466475" {!! $inv('kilometraje') !!}>
                        <x-campo-error name="kilometraje" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="color">Color</label>
                        <input type="text" id="color" name="color" value="{{ old('color') }}" maxlength="50"
                            class="form-input" placeholder="Ej: Blanco" {!! $inv('color') !!}>
                        <x-campo-error name="color" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="numero_motor">Número de motor</label>
                        <input type="text" id="numero_motor" name="numero_motor" value="{{ old('numero_motor') }}" maxlength="20"
                            autocomplete="off" spellcheck="false" class="form-input" {!! $inv('numero_motor') !!}>
                        <x-campo-error name="numero_motor" />
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Situación --}}
        <section class="erp-card alta-seccion" aria-labelledby="sec-situacion">
            <div class="erp-card-header !justify-start gap-3">
                <span class="alta-paso" aria-hidden="true">2</span>
                <h2 id="sec-situacion">Situación y origen</h2>
            </div>
            <div class="erp-card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado *</label>
                        <select id="estado" name="estado" required class="form-input" aria-describedby="estadoAyuda" {!! $inv('estado') !!}>
                            @foreach(['DISPONIBLE' => 'Disponible para la venta', 'EN_PREPARACION' => 'En preparación (taller)', 'EN_TRANSITO' => 'En tránsito', 'EN_ADUANA' => 'En aduana', 'TOMA' => 'Tomado en canje'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('estado', 'DISPONIBLE') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p id="estadoAyuda" class="alta-ayuda">Solo los disponibles aparecen al vender.</p>
                        <x-campo-error name="estado" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="proveedor_id">Proveedor</label>
                        <select id="proveedor_id" name="proveedor_id" class="form-input" {!! $inv('proveedor_id') !!}>
                            <option value="">Sin proveedor</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" @selected((string) old('proveedor_id') === (string) $p->id)>{{ $p->razon_social }}</option>
                            @endforeach
                        </select>
                        <x-campo-error name="proveedor_id" />
                    </div>
                </div>
            </div>
        </section>

        {{-- 3. Costo y precios --}}
        <section class="erp-card alta-seccion" aria-labelledby="sec-costo">
            <div class="erp-card-header !justify-start gap-3">
                <span class="alta-paso" aria-hidden="true">3</span>
                <h2 id="sec-costo">Costo y precios de venta</h2>
            </div>
            <div class="erp-card-body">
                <div class="form-grid">
                    <fieldset class="form-group" style="grid-column:1/-1">
                        <legend class="form-label mb-1.5">Moneda en que se compró *</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach(['USD' => ['Dólar', 'us'], 'PYG' => ['Guaraní', 'py'], 'BRL' => ['Real', 'br']] as $m => [$nombre, $bandera])
                                <label class="cursor-pointer">
                                    <input type="radio" name="moneda_costo" value="{{ $m }}" class="peer sr-only" @checked(old('moneda_costo', 'USD') === $m)>
                                    <div class="flex items-center gap-3 p-3 rounded-xl border-2 border-border bg-surface-2 transition-all hover:bg-surface-3 peer-checked:border-primary peer-checked:bg-primary/5 peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                                        <img src="https://flagcdn.com/w40/{{ $bandera }}.png" class="w-8 h-6 rounded object-cover" alt="">
                                        <div class="min-w-0">
                                            <span class="block text-sm font-bold text-foreground">{{ $m }} <span class="font-normal text-muted-foreground">· {{ $nombre }}</span></span>
                                            <span class="block text-xs text-muted-foreground" data-tasa-de="{{ $m }}">{{ $m === 'USD' ? 'Moneda base' : 'Cargando cotización…' }}</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="form-group">
                        <label class="form-label" for="costo_origen_moneda">Costo de compra en <span data-moneda-actual>{{ old('moneda_costo', 'USD') }}</span> *</label>
                        <input type="number" id="costo_origen_moneda" name="costo_origen_moneda" value="{{ old('costo_origen_moneda') }}"
                            step="0.01" min="0" required inputmode="decimal" class="form-input"
                            @if($errors->has('costo_origen_moneda') || $errors->has('costo_origen_usd')) aria-invalid="true" aria-describedby="costo_origen_moneda-error" @endif>
                        <p class="alta-ayuda" id="costoUsd" aria-live="polite"></p>
                        <x-campo-error name="costo_origen_moneda" />
                        @error('costo_origen_usd')
                            @unless($errors->has('costo_origen_moneda'))
                                <p id="costo_origen_moneda-error" class="text-xs mt-1" style="color: var(--danger);">{{ $message }}</p>
                            @endunless
                        @enderror
                    </div>
                    <div class="form-group" id="tasaGrupo">
                        <label class="form-label" for="tasa_cambio_compra">Cotización (<span data-moneda-actual>{{ old('moneda_costo', 'USD') }}</span> por 1 USD)</label>
                        <input type="number" id="tasa_cambio_compra" name="tasa_cambio_compra" value="{{ old('moneda_costo', 'USD') !== 'USD' ? old('tasa_cambio_compra') : '' }}"
                            step="any" min="0" inputmode="decimal" class="form-input" {!! $inv('tasa_cambio_compra') !!}>
                        <p class="alta-ayuda" id="tasaFuente" aria-live="polite"></p>
                        <x-campo-error name="tasa_cambio_compra" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="precio_contado_usd">Precio de venta contado (USD)</label>
                        <input type="number" id="precio_contado_usd" name="precio_contado_usd" value="{{ old('precio_contado_usd') }}"
                            step="0.01" min="0" inputmode="decimal" class="form-input" {!! $inv('precio_contado_usd') !!}>
                        <p class="alta-ayuda" data-margen-de="precio_contado_usd"></p>
                        <x-campo-error name="precio_contado_usd" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="precio_cuotas_usd">Precio de venta en cuotas (USD)</label>
                        <input type="number" id="precio_cuotas_usd" name="precio_cuotas_usd" value="{{ old('precio_cuotas_usd') }}"
                            step="0.01" min="0" inputmode="decimal" class="form-input" {!! $inv('precio_cuotas_usd') !!}>
                        <p class="alta-ayuda" data-margen-de="precio_cuotas_usd"></p>
                        <x-campo-error name="precio_cuotas_usd" />
                    </div>
                </div>
            </div>
        </section>

        {{-- 4. Fotos --}}
        <section class="erp-card alta-seccion" aria-labelledby="sec-fotos">
            <div class="erp-card-header !justify-start gap-3">
                <span class="alta-paso" aria-hidden="true">4</span>
                <h2 id="sec-fotos">Fotos</h2>
                <span class="ml-auto text-xs text-muted-foreground" id="fotosContador" aria-live="polite">0 de 10</span>
            </div>
            <div class="erp-card-body">
                <button type="button" class="img-drop-zone" id="dropZone" aria-describedby="fotosAyuda">
                    <svg class="w-9 h-9 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke-width="1.25" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <span class="block text-sm">Arrastrá las fotos aquí o <strong class="text-primary">elegí desde tu equipo</strong></span>
                    <span class="block text-xs mt-1" id="fotosAyuda">JPG, PNG o WEBP · hasta 10 fotos de 5 MB · la primera es la portada</span>
                </button>
                <input type="file" id="imageInput" name="imagenes[]" multiple accept="image/jpeg,image/png,image/webp" class="hidden">
                <p class="text-xs mt-2" id="fotosAviso" role="status" style="color: var(--warning);"></p>
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mt-3" id="previewGrid"></div>
                <x-campo-error name="imagenes" />
            </div>
        </section>

        {{-- 5. Ficha técnica y web (opcional) --}}
        <details class="erp-card alta-seccion alta-opcional" @if($abrirTecnicos || old('publicar_en_web')) open @endif>
            <summary class="erp-card-header !justify-start gap-3">
                <span class="alta-paso" aria-hidden="true">5</span>
                <span class="flex-1">
                    <span class="block font-semibold text-foreground">Ficha técnica y sitio web</span>
                    <span class="block text-xs text-muted-foreground font-normal">Opcional · se puede completar después desde Editar</span>
                </span>
                <svg class="alta-chevron w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </summary>
            <div class="erp-card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="motor_descripcion">Motor</label>
                        <input type="text" id="motor_descripcion" name="motor_descripcion" value="{{ old('motor_descripcion') }}" maxlength="100"
                            placeholder="Ej: DC13 500 CV Euro 6" class="form-input" {!! $inv('motor_descripcion') !!}>
                        <x-campo-error name="motor_descripcion" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tipo_traccion">Tracción</label>
                        <select id="tipo_traccion" name="tipo_traccion" class="form-input">
                            <option value="">Sin especificar</option>
                            @foreach(['4x2', '4x4', '6x2', '6x4', '8x4', '6x6', '8x8'] as $t)
                                <option value="{{ $t }}" @selected(old('tipo_traccion') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="potencia_hp">Potencia (HP)</label>
                        <input type="number" id="potencia_hp" name="potencia_hp" value="{{ old('potencia_hp') }}" min="0" max="9999"
                            inputmode="numeric" placeholder="Ej: 500" class="form-input" {!! $inv('potencia_hp') !!}>
                        <x-campo-error name="potencia_hp" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="par_nm">Par motor (Nm)</label>
                        <input type="number" id="par_nm" name="par_nm" value="{{ old('par_nm') }}" min="0" max="99999"
                            inputmode="numeric" placeholder="Ej: 2550" class="form-input" {!! $inv('par_nm') !!}>
                        <x-campo-error name="par_nm" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tipo_transmision">Transmisión</label>
                        <select id="tipo_transmision" name="tipo_transmision" class="form-input">
                            <option value="">Sin especificar</option>
                            @foreach(['MANUAL' => 'Manual', 'AUTOMATICA' => 'Automática', 'AUTOMATIZADA' => 'Automatizada (AMT)'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('tipo_transmision') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cabina">Cabina</label>
                        <input type="text" id="cabina" name="cabina" value="{{ old('cabina') }}" maxlength="80"
                            placeholder="Ej: Highline, Topline, Day Cab" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="norma_euro">Norma de emisión</label>
                        <input type="text" id="norma_euro" name="norma_euro" value="{{ old('norma_euro') }}" maxlength="10"
                            placeholder="Ej: Euro 6" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="peso_bruto_t">Peso bruto vehicular (t)</label>
                        <input type="number" id="peso_bruto_t" name="peso_bruto_t" value="{{ old('peso_bruto_t') }}" min="0" max="999"
                            step="0.01" inputmode="decimal" placeholder="Ej: 48" class="form-input" {!! $inv('peso_bruto_t') !!}>
                        <x-campo-error name="peso_bruto_t" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="deposito_litros">Depósito de combustible (L)</label>
                        <input type="number" id="deposito_litros" name="deposito_litros" value="{{ old('deposito_litros') }}" min="0" max="9999"
                            inputmode="numeric" placeholder="Ej: 600" class="form-input" {!! $inv('deposito_litros') !!}>
                        <x-campo-error name="deposito_litros" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="neumaticos">Neumáticos</label>
                        <input type="text" id="neumaticos" name="neumaticos" value="{{ old('neumaticos') }}" maxlength="50"
                            placeholder="Ej: 295/60 R22.5" class="form-input">
                    </div>
                </div>

                <div class="form-group mt-5">
                    <label class="form-label" for="equipamiento-input">Equipamiento destacado</label>
                    <div id="equipamiento-tags" class="flex flex-wrap gap-2 mb-2">
                        @foreach(old('equipamiento', []) as $item)
                            <span class="inline-flex items-center gap-1 pl-3 pr-1 py-1 rounded-full text-xs border border-border bg-surface-2">
                                {{ $item }}
                                <button type="button" class="tag-quitar w-6 h-6 inline-flex items-center justify-center rounded-full hover:bg-surface-3" aria-label="Quitar {{ $item }}">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                                <input type="hidden" name="equipamiento[]" value="{{ $item }}">
                            </span>
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="equipamiento-input" maxlength="80" placeholder="Ej: ABS, aire acondicionado, GPS"
                            class="form-input flex-1" aria-describedby="equipamientoAyuda">
                        <button type="button" id="equipamientoAgregar" class="btn btn-ghost">Agregar</button>
                    </div>
                    <p id="equipamientoAyuda" class="alta-ayuda">Escribí y presioná Enter; podés pegar varios separados por coma.</p>
                </div>

                <div class="form-group mt-5">
                    <label class="form-label" for="descripcion_publica">Descripción para el sitio web</label>
                    <textarea id="descripcion_publica" name="descripcion_publica" rows="3" maxlength="2000"
                        class="form-input" {!! $inv('descripcion_publica') !!}>{{ old('descripcion_publica') }}</textarea>
                    <x-campo-error name="descripcion_publica" />
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:gap-6 mt-5">
                    <label class="flex items-center gap-3 cursor-pointer text-sm">
                        <input type="hidden" name="publicar_en_web" value="0">
                        <input type="checkbox" name="publicar_en_web" value="1" @checked(old('publicar_en_web'))
                            class="w-5 h-5 rounded" style="accent-color:var(--primary)">
                        Publicar en el sitio web
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer text-sm">
                        <input type="hidden" name="mostrar_precio" value="0">
                        <input type="checkbox" name="mostrar_precio" value="1" @checked(old('mostrar_precio', true))
                            class="w-5 h-5 rounded" style="accent-color:var(--primary)">
                        Mostrar el precio al público
                    </label>
                </div>
            </div>
        </details>

        <div class="alta-acciones flex items-center justify-end gap-3 -mx-4 px-4 py-3 sm:mx-0 sm:rounded-b-xl">
            <p class="mr-auto text-xs text-muted-foreground hidden sm:block">Los campos con * son obligatorios.</p>
            <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Guardar vehículo
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('numero_chasis').form;
                const $ = (id) => document.getElementById(id);
                const fmt = (n, d) => Number(n).toLocaleString('es-PY', { minimumFractionDigits: d, maximumFractionDigits: d });

                // Tras un error de validación, llevar el foco al resumen para lectores de pantalla y teclado
                $('resumenErrores')?.focus();

                // ── Chasis: mayúsculas y contador de 17 ──────────────
                const chasis = $('numero_chasis');
                const contarChasis = () => {
                    const pos = chasis.selectionStart;
                    chasis.value = chasis.value.toUpperCase().replace(/\s+/g, '');
                    chasis.setSelectionRange(pos, pos);
                    $('chasisContador').textContent = `${chasis.value.length}/17`;
                };
                chasis.addEventListener('input', contarChasis);
                contarChasis();

                // ── Costo, cotización (La Moneda) y margen ───────────
                const costoInput = $('costo_origen_moneda');
                const tasaInput = $('tasa_cambio_compra');
                let rates = {}, detalle = {};
                let tasaManual = @json(filled(old('tasa_cambio_compra')) && old('moneda_costo', 'USD') !== 'USD');
                const moneda = () => form.querySelector('input[name="moneda_costo"]:checked')?.value || 'USD';
                const costoUsd = () => {
                    const costo = parseFloat(costoInput.value) || 0;
                    const tasa = moneda() === 'USD' ? 1 : (parseFloat(tasaInput.value) || 0);
                    return costo > 0 && tasa > 0 ? Math.round(costo / tasa * 100) / 100 : 0;
                };

                function render() {
                    const m = moneda(), esUsd = m === 'USD';
                    $('tasaGrupo').style.display = esUsd ? 'none' : '';
                    tasaInput.disabled = esUsd;
                    tasaInput.required = !esUsd;
                    if (!esUsd && !tasaManual) tasaInput.value = rates[m] ?? '';
                    form.querySelectorAll('[data-moneda-actual]').forEach(el => el.textContent = m);
                    for (const k of ['PYG', 'BRL']) {
                        form.querySelector(`[data-tasa-de="${k}"]`).textContent = rates[k] ? `1 USD = ${fmt(rates[k], k === 'PYG' ? 0 : 2)}` : 'Sin cotización';
                    }
                    const det = detalle[m];
                    $('tasaFuente').textContent = tasaManual ? 'Cotización modificada a mano.'
                        : det ? `Fuente: ${det.fuente} · ${new Date(det.fecha + 'T00:00').toLocaleDateString('es-PY')}`
                        : 'Sin cotización registrada: ingresala a mano.';
                    const usd = costoUsd();
                    $('costoUsd').textContent = !esUsd && usd > 0 ? `Equivale a US$ ${fmt(usd, 2)}` : '';

                    // Margen estimado de cada precio respecto al costo de compra
                    form.querySelectorAll('[data-margen-de]').forEach(el => {
                        const precio = parseFloat($(el.dataset.margenDe).value) || 0;
                        if (!(precio > 0 && usd > 0)) { el.textContent = ''; return; }
                        const margen = precio - usd;
                        el.textContent = `Margen sobre el costo: US$ ${fmt(margen, 2)} (${fmt(margen / usd * 100, 1)} %)`;
                        el.style.color = margen < 0 ? 'var(--danger)' : '';
                    });
                }

                async function cargarCotizacion() {
                    try {
                        const res = await fetch(@json(route('api.cotizaciones.today')), { headers: { Accept: 'application/json' } });
                        if (!res.ok) throw new Error(res.status);
                        const d = await res.json();
                        detalle = d.detalle || {};
                        rates = {};
                        for (const k of ['PYG', 'BRL']) if (detalle[k]) rates[k] = d[k];
                    } catch (e) {
                        rates = {}; detalle = {};
                    }
                    render();
                }

                form.querySelectorAll('input[name="moneda_costo"]').forEach(r => r.addEventListener('change', () => { tasaManual = false; render(); }));
                tasaInput.addEventListener('input', () => { tasaManual = true; render(); });
                ['costo_origen_moneda', 'precio_contado_usd', 'precio_cuotas_usd'].forEach(id => $(id).addEventListener('input', render));
                render();
                cargarCotizacion();

                // ── Fotos: arrastrar o elegir, máximo 10 de 5 MB ─────
                const MAX_FOTOS = 10, MAX_BYTES = 5 * 1024 * 1024;
                const dropZone = $('dropZone'), imageInput = $('imageInput'), previewGrid = $('previewGrid');
                let fotos = [];

                dropZone.addEventListener('click', () => imageInput.click());
                dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
                dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
                dropZone.addEventListener('drop', (e) => { e.preventDefault(); dropZone.classList.remove('dragover'); agregarFotos(e.dataTransfer.files); });
                imageInput.addEventListener('change', (e) => agregarFotos(e.target.files));

                function agregarFotos(lista) {
                    const avisos = [];
                    for (const f of lista) {
                        if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { avisos.push(`${f.name}: formato no admitido`); continue; }
                        if (f.size > MAX_BYTES) { avisos.push(`${f.name}: pesa más de 5 MB`); continue; }
                        if (fotos.length >= MAX_FOTOS) { avisos.push('Se alcanzó el máximo de 10 fotos'); break; }
                        fotos.push(f);
                    }
                    $('fotosAviso').textContent = avisos.join(' · ');
                    sincronizar();
                }

                function sincronizar() {
                    const dt = new DataTransfer();
                    fotos.forEach(f => dt.items.add(f));
                    imageInput.files = dt.files;
                    $('fotosContador').textContent = `${fotos.length} de ${MAX_FOTOS}`;
                    previewGrid.replaceChildren(...fotos.map((f, i) => {
                        const div = document.createElement('div');
                        div.className = 'img-preview-item';
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(f);
                        img.alt = `Foto ${i + 1}: ${f.name}`;
                        img.onload = () => URL.revokeObjectURL(img.src);
                        const quitar = document.createElement('button');
                        quitar.type = 'button';
                        quitar.className = 'remove-img';
                        quitar.setAttribute('aria-label', `Quitar ${f.name}`);
                        quitar.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>';
                        quitar.addEventListener('click', () => { fotos.splice(i, 1); sincronizar(); });
                        div.append(img, quitar);
                        if (i === 0) {
                            const portada = document.createElement('span');
                            portada.className = 'portada';
                            portada.textContent = 'Portada';
                            div.append(portada);
                        }
                        return div;
                    }));
                }

                // ── Equipamiento: etiquetas con Enter o coma ─────────
                const tags = $('equipamiento-tags'), tagInput = $('equipamiento-input');
                function agregarTags() {
                    const existentes = new Set([...tags.querySelectorAll('input')].map(i => i.value.toLowerCase()));
                    tagInput.value.split(',').map(v => v.trim()).filter(Boolean).forEach(val => {
                        if (existentes.has(val.toLowerCase())) return;
                        existentes.add(val.toLowerCase());
                        const span = document.createElement('span');
                        span.className = 'inline-flex items-center gap-1 pl-3 pr-1 py-1 rounded-full text-xs border border-border bg-surface-2';
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'tag-quitar w-6 h-6 inline-flex items-center justify-center rounded-full hover:bg-surface-3';
                        btn.setAttribute('aria-label', `Quitar ${val}`);
                        btn.innerHTML = '<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>';
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'equipamiento[]';
                        hidden.value = val;
                        span.append(document.createTextNode(val), btn, hidden);
                        tags.appendChild(span);
                    });
                    tagInput.value = '';
                }
                tagInput.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); agregarTags(); } });
                $('equipamientoAgregar').addEventListener('click', agregarTags);
                tags.addEventListener('click', (e) => { const b = e.target.closest('.tag-quitar'); if (b) { b.closest('span').remove(); tagInput.focus(); } });

                // Enter en un campo de texto no envía el formulario largo por accidente
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') e.preventDefault();
                });
            })();
        </script>
    @endpush
@endsection
