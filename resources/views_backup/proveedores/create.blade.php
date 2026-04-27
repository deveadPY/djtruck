@extends('layouts.app')
@section('title', 'Nuevo Proveedor')
@section('page-title', '🏷️ Registrar Proveedor')

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

    <div style="margin-bottom:1rem"><a href="{{ route('proveedores.index') }}" class="btn btn-ghost">← Volver</a></div>

    <div class="card">
        <div class="card-header">
            <h2>Datos del proveedor</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('proveedores.store') }}" data-confirm="Confirmar registro de proveedor">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Razón Social *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" required maxlength="200">
                    </div>
                    <div class="form-group">
                        <label>Nombre Fantasía</label>
                        <input type="text" name="nombre_fantasia" value="{{ old('nombre_fantasia') }}" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label>RUC / RUT / NIT</label>
                        <input type="text" name="ruc_rut_nit" value="{{ old('ruc_rut_nit') }}" maxlength="30">
                    </div>
                    <div class="form-group">
                        <label>País *</label>
                        <select name="pais" required>
                            <option value="PY" {{ old('pais', 'PY') == 'PY' ? 'selected' : '' }}>Paraguay</option>
                            <option value="BR" {{ old('pais') == 'BR' ? 'selected' : '' }}>Brasil</option>
                            <option value="US" {{ old('pais') == 'US' ? 'selected' : '' }}>Estados Unidos</option>
                            <option value="UY" {{ old('pais') == 'UY' ? 'selected' : '' }}>Uruguay</option>
                            <option value="AR" {{ old('pais') == 'AR' ? 'selected' : '' }}>Argentina</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Proveedor *</label>
                        <select name="tipo" required>
                            @foreach(['DISTRIBUIDOR' => 'Distribuidor / Repuestos', 'FABRICANTE' => 'Fabricante / Origen', 'SERVICIO' => 'Servicios Técnicos', 'OTRO' => 'Gastos Operativos / Generales'] as $v => $l)
                                <option value="{{ $v }}" {{ old('tipo') == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Moneda principal *</label>
                        <select name="moneda_principal" required>
                            <option value="USD" {{ old('moneda_principal', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="PYG" {{ old('moneda_principal') == 'PYG' ? 'selected' : '' }}>PYG</option>
                            <option value="BRL" {{ old('moneda_principal') == 'BRL' ? 'selected' : '' }}>BRL</option>
                        </select>
                    </div>
                </div>
                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('proveedores.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar proveedor</button>
                </div>
            </form>
        </div>
    </div>
@endsection