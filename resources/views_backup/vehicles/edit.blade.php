@extends('layouts.app')
@section('title', 'Editar Vehículo')
@section('page-title', '✏️ Editar Vehículo')

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

        label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em
        }

        input,
        select {
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
        select:focus {
            border-color: var(--primary)
        }
    </style>
@endpush

@section('content')
    @if($errors->any())
        <div
            style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    {{ $errors->first() }}</div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">← Volver</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>{{ $vehiculo->marca }} {{ $vehiculo->modelo }} — Chasis: {{ $vehiculo->numero_chasis }}</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('vehicles.update', $vehiculo->id) }}" data-confirm="Confirmar actualización de vehículo">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label>Marca *</label>
                        <input type="text" name="marca" value="{{ old('marca', $vehiculo->marca) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="modelo" value="{{ old('modelo', $vehiculo->modelo) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Año *</label>
                        <input type="number" name="año" value="{{ old('año', $vehiculo->año) }}" min="1980" max="2030"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color" value="{{ old('color', $vehiculo->color) }}">
                    </div>
                    <div class="form-group">
                        <label>Tipo de vehículo *</label>
                        <select name="tipo_vehiculo" required>
                            @foreach(['CAMION_RIGIDO' => 'Camión Rígido', 'CAMION_TRACTO' => 'Camión Tracto', 'SEMI_REMOLQUE' => 'Semi Remolque', 'FURGON' => 'Furgón', 'VOLQUETE' => 'Volquete', 'CISTERNA' => 'Cisterna', 'OTRO' => 'Otro'] as $val => $label)
                                <option value="{{ $val }}" {{ old('tipo_vehiculo', $vehiculo->tipo_vehiculo) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado *</label>
                        <select name="estado" required>
                            @foreach(['EN_TRANSITO', 'EN_ADUANA', 'EN_PREPARACION', 'DISPONIBLE', 'RESERVADO', 'TOMA', 'BAJA'] as $val)
                                <option value="{{ $val }}" {{ old('estado', $vehiculo->estado) == $val ? 'selected' : '' }}>
                                    {{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kilometraje</label>
                        <input type="number" name="kilometraje" value="{{ old('kilometraje', $vehiculo->kilometraje) }}"
                            min="0">
                    </div>
                    <div class="form-group">
                        <label>Proveedor</label>
                        <select name="proveedor_id">
                            <option value="">— Sin proveedor —</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ old('proveedor_id', $vehiculo->proveedor_id) == $p->id ? 'selected' : '' }}>{{ $p->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Moneda de costo *</label>
                        <select name="moneda_costo" required>
                            @foreach(['USD', 'PYG', 'BRL'] as $m)
                                <option value="{{ $m }}" {{ old('moneda_costo', $vehiculo->moneda_costo) == $m ? 'selected' : '' }}>
                                    {{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Costo en moneda origen *</label>
                        <input type="number" name="costo_origen_moneda"
                            value="{{ old('costo_origen_moneda', $vehiculo->costo_origen_moneda) }}" step="0.01" min="0"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Costo en USD *</label>
                        <input type="number" name="costo_origen_usd"
                            value="{{ old('costo_origen_usd', $vehiculo->costo_origen_usd) }}" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Precio venta sugerido (USD)</label>
                        <input type="number" name="precio_venta_sugerido_usd"
                            value="{{ old('precio_venta_sugerido_usd', $vehiculo->precio_venta_sugerido_usd) }}" step="0.01"
                            min="0">
                    </div>
                </div>
                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Actualizar</button>
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
            } catch(e) { console.error('Error fetching rates', e); }
        }

        function calcularTotalUsd() {
            const costoMoneda = parseFloat(document.querySelector('input[name="costo_origen_moneda"]').value) || 0;
            const moneda = document.querySelector('select[name="moneda_costo"]').value;
            let costoUsd = costoMoneda;

            if(moneda === 'PYG') {
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

        // Fetch initial rates on load
        fetchRates();
    </script>
    @endpush
@endsection