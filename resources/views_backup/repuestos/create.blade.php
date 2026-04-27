@extends('layouts.app')
@section('title', 'Nuevo Repuesto')
@section('page-title', '🔧 Registrar Repuesto')

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
            {{ $errors->first() }}
    </div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('repuestos.index') }}" class="btn btn-ghost">← Volver</a></div>

    <div class="card">
        <div class="card-header">
            <h2>Nuevo repuesto / pieza</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('repuestos.store') }}" data-confirm="Confirmar registro de repuesto">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Código *</label>
                        <input type="text" name="codigo" value="{{ old('codigo') }}" placeholder="FLT-001" required
                            maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Unidad de medida *</label>
                        <select name="unidad_medida" required>
                            @foreach(['UND' => 'Unidad', 'LTS' => 'Litros', 'KG' => 'Kilogramos', 'MT' => 'Metros', 'JGO' => 'Juego', 'PAR' => 'Par'] as $v => $l)
                                <option value="{{ $v }}" {{ old('unidad_medida') == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Descripción *</label>
                        <input type="text" name="descripcion" value="{{ old('descripcion') }}"
                            placeholder="Filtro de aceite MB Actros" required maxlength="300">
                    </div>
                    <div class="form-group">
                        <label>Marca compatible</label>
                        <input type="text" name="marca_compatible" value="{{ old('marca_compatible') }}"
                            placeholder="Mercedes-Benz">
                    </div>
                    <div class="form-group">
                        <label>Stock actual</label>
                        <input type="number" name="stock_actual" value="{{ old('stock_actual', 0) }}" step="0.001" min="0">
                    </div>
                    <div class="form-group">
                        <label>Stock mínimo</label>
                        <input type="number" name="stock_minimo" value="{{ old('stock_minimo', 0) }}" step="0.001" min="0">
                    </div>
                    <div class="form-group" style="grid-column:1/-1; display:flex; gap:1.25rem;">
                        <div style="flex:1;">
                            <label>Moneda de Costo/Venta *</label>
                            <select name="moneda_origen" id="moneda_sel">
                                <option value="USD" {{ old('moneda_origen', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="PYG" {{ old('moneda_origen') == 'PYG' ? 'selected' : '' }}>PYG</option>
                                <option value="BRL" {{ old('moneda_origen') == 'BRL' ? 'selected' : '' }}>BRL</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label>Tasa de Cambio</label>
                            <input type="number" name="tasa_cambio" value="1" step="0.0001" readonly
                                style="background:var(--surface1); cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Costo promedio original</label>
                        <input type="number" name="costo_promedio_moneda" value="{{ old('costo_promedio_moneda') }}"
                            step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Precio de venta original</label>
                        <input type="number" name="precio_venta_moneda" value="{{ old('precio_venta_moneda') }}" step="0.01"
                            min="0">
                    </div>
                    <div class="form-group">
                        <label>Costo promedio (USD) *</label>
                        <input type="number" name="costo_promedio_usd" value="{{ old('costo_promedio_usd') }}" step="0.01"
                            min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Precio de venta (USD)</label>
                        <input type="number" name="precio_venta_usd" value="{{ old('precio_venta_usd') }}" step="0.01"
                            min="0">
                    </div>
                </div>
                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('repuestos.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar repuesto</button>
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
                    calcularTotalesUsd();
                } catch (e) { console.error('Error fetching rates', e); }
            }

            function calcularTotalesUsd() {
                const costoMo = parseFloat(document.querySelector('input[name="costo_promedio_moneda"]').value) || 0;
                const precioMo = parseFloat(document.querySelector('input[name="precio_venta_moneda"]').value) || 0;
                const moneda = document.getElementById('moneda_sel').value;

                let costoUsd = costoMo, precioUsd = precioMo;
                let tasaUsada = null;

                if (moneda === 'PYG') {
                    const rate = parseFloat(currentRates.PYG) || 1;
                    costoUsd = costoMo / rate;
                    precioUsd = precioMo / rate;
                    tasaUsada = rate;
                } else if (moneda === 'BRL') {
                    const rate = parseFloat(currentRates.BRL) || 1;
                    costoUsd = costoMo / rate;
                    precioUsd = precioMo / rate;
                    tasaUsada = rate;
                }

                document.querySelector('input[name="costo_promedio_usd"]').value = costoUsd ? costoUsd.toFixed(2) : '';
                document.querySelector('input[name="precio_venta_usd"]').value = precioUsd ? precioUsd.toFixed(2) : '';

                if (tasaUsada) {
                    document.querySelector('input[name="tasa_cambio"]').value = tasaUsada.toFixed(2);
                } else {
                    document.querySelector('input[name="tasa_cambio"]').value = '1';
                }
            }

            document.getElementById('moneda_sel').addEventListener('change', calcularTotalesUsd);
            document.querySelector('input[name="costo_promedio_moneda"]').addEventListener('input', calcularTotalesUsd);
            document.querySelector('input[name="precio_venta_moneda"]').addEventListener('input', calcularTotalesUsd);

            // Fetch initial rates on load
            fetchRates();
        </script>
    @endpush
@endsection