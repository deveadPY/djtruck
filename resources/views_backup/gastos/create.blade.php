@extends('layouts.app')
@section('title', 'Registrar Gasto')
@section('page-title', '💸 Registrar Gasto de Vehículo')

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

        .vehiculo-badge {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem
        }
    </style>
@endpush

@section('content')
    @if($errors->any())
        <div
            style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
    {{ $errors->first() }}</div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">← Volver al
            vehículo</a></div>

    <div class="vehiculo-badge">
        <span style="font-size:2rem">🚛</span>
        <div>
            <div style="font-weight:700">{{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->año }})</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Chasis: {{ $vehiculo->numero_chasis }} | Valor libro
                actual: <strong style="color:var(--accent)">$
                    {{ number_format($vehiculo->costo_origen_usd + $vehiculo->total_gastos_usd, 2, ',', '.') }} USD</strong></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Nuevo gasto</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('gastos.store', $vehiculo->id) }}" data-confirm="Confirmar registro de gasto">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Concepto *</label>
                        <input type="text" name="concepto" value="{{ old('concepto') }}"
                            placeholder="Ej: Reparación de frenos" required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Categoría *</label>
                        <select name="categoria" required>
                            @foreach([
                                    'REPARACION_MECANICA' => 'Reparación Mecánica',
                                    'CHAPERIA_PINTURA' => 'Chapería y Pintura',
                                    'ELECTRICIDAD' => 'Electricidad',
                                    'NEUMATICOS' => 'Neumáticos',
                                    'DERECHOS_ADUANA' => 'Derechos de Aduana',
                                    'IMPUESTO_IMPORTACION' => 'Impuesto Importación',
                                    'LOGISTICA' => 'Logística / Flete',
                                    'DOCUMENTACION' => 'Documentación',
                                    'OTROS_PREPARACION' => 'Otros',
                                ] as $v => $l)
                                <option value="{{ $v }}" {{ old('categoria') == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Origen *</label>
                        <select name="origen_tipo" required>
                            @foreach([
                                    'FACTURA_PROVEEDOR' => 'Factura Proveedor',
                                    'STOCK_REPUESTO' => 'Repuesto en Stock',
                                    'MANO_OBRA' => 'Mano de Obra',
                                    'ADUANA' => 'Aduana',
                                    'FLETE' => 'Flete',
                                    'SEGURO' => 'Seguro',
                                    'OTRO' => 'Otro',
                                ] as $v => $l)
                                <option value="{{ $v }}" {{ old('origen_tipo') == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Repuesto (opcional)</label>
                        <select name="repuesto_id">
                            <option value="">— Ninguno —</option>
                            @foreach($repuestos as $r)
                                <option value="{{ $r->id }}" {{ old('repuesto_id') == $r->id ? 'selected' : '' }}>{{ $r->codigo }} — {{ Str::limit($r->descripcion, 40) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad repuesto</label>
                        <input type="number" name="repuesto_cantidad" value="{{ old('repuesto_cantidad') }}" step="0.001" min="0">
                    </div>
                    <div class="form-group" style="grid-column:1/-1; display:flex; gap:1.25rem;">
                        <div style="flex:1;">
                            <label>Moneda *</label>
                            <select name="moneda" required>
                                @foreach(['USD', 'PYG', 'BRL'] as $m)
                                    <option value="{{ $m }}" {{ old('moneda', 'USD') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label>Tasa de Cambio</label>
                            <input type="number" name="tasa_cambio" value="1" step="0.0001" readonly style="background:var(--surface1); cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Monto en moneda *</label>
                        <input type="number" name="monto_moneda" value="{{ old('monto_moneda') }}" step="0.01" min="0" required>
                    </div>
                <div class="form-group">
                    <label>Monto en USD *</label>
                    <input type="number" name="monto_usd" value="{{ old('monto_usd') }}" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Fecha del gasto *</label>
                    <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', date('Y-m-d')) }}" required>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Descripción / Observaciones</label>
                    <textarea name="descripcion" rows="2">{{ old('descripcion') }}</textarea>
                </div>
            </div>
            <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                <a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">💾 Registrar gasto</button>
            </div>
        </form>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentRates = { PYG: 1, BRL: 1 };

        async function fetchRates() {
            try {
                const fecha = document.querySelector('input[name="fecha_gasto"]').value || new Date().toISOString().split('T')[0];
                const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${fecha}`);
                const data = await res.json();
                currentRates = data;
                calcularTotalUsd();
            } catch(e) { console.error('Error fetching rates', e); }
        }

        function calcularTotalUsd() {
            const monto = parseFloat(document.querySelector('input[name="monto_moneda"]').value) || 0;
            const moneda = document.querySelector('select[name="moneda"]').value;
            let totalUsd = monto;
            let tasaUsada = null;

            if(moneda === 'PYG') {
                const rate = parseFloat(currentRates.PYG) || 1;
                totalUsd = monto / rate;
                tasaUsada = rate;
            } else if (moneda === 'BRL') {
                const rate = parseFloat(currentRates.BRL) || 1;
                totalUsd = monto / rate;
                tasaUsada = rate;
            }

            document.querySelector('input[name="monto_usd"]').value = totalUsd.toFixed(2);
            if (tasaUsada) {
                document.querySelector('input[name="tasa_cambio"]').value = tasaUsada.toFixed(2);
            } else {
                document.querySelector('input[name="tasa_cambio"]').value = '1';
            }
        }

        document.querySelector('input[name="fecha_gasto"]').addEventListener('change', fetchRates);
        document.querySelector('select[name="moneda"]').addEventListener('change', calcularTotalUsd);
        document.querySelector('input[name="monto_moneda"]').addEventListener('input', calcularTotalUsd);

        // Fetch initial rates on load
        fetchRates();
    </script>
    @endpush
@endsection
