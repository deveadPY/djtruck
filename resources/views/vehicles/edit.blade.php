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
                    const res = await fetch(`{{ route('cotizaciones.tasas-hoy') }}?fecha=${fecha}`);
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
        </script>
    @endpush
@endsection