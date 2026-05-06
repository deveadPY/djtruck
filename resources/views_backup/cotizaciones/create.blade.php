@extends('layouts.app')
@section('title', 'Actualizar Cotización')
@section('page-title', '💱 Configurar Cambio del Día')

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

    <div style="margin-bottom:1rem"><a href="{{ route('cotizaciones.index') }}" class="btn btn-ghost">← Volver</a></div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2>Fijar Tipo de Cambio</h2>
                <div style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem">Se afectarán los nuevos cálculos
                    desde la fecha seleccionada.</div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('cotizaciones.store') }}" data-confirm="Confirmar registro de cotización">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Moneda Destino (vs USD) *</label>
                        <select name="moneda_destino" required>
                            <option value="PYG" {{ old('moneda_destino') == 'PYG' ? 'selected' : '' }}>Guaraníes (PYG)
                            </option>
                            <option value="BRL" {{ old('moneda_destino') == 'BRL' ? 'selected' : '' }}>Reales (BRL)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha de la cotización *</label>
                        <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Cambio VENTA (Usado p/ cálculo principal) *</label>
                        <input type="number" name="venta" value="{{ old('venta') }}" step="0.01" min="1" required
                            placeholder="Ej. 7500 o 5.80">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Cambio COMPRA *</label>
                        <input type="number" name="compra" value="{{ old('compra') }}" step="0.01" min="1" required
                            placeholder="Ej. 7400 o 5.60">
                    </div>
                </div>
                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('cotizaciones.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar cotización</button>
                </div>
            </form>
        </div>
    </div>
@endsection