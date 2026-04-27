@extends('layouts.app')
@section('title', 'Editar Cliente')
@section('page-title', '👥 Editar Cliente')

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

        small.text-muted {
            font-size: .75rem;
            color: var(--text-muted);
            margin-top: .15rem
        }
    </style>
@endpush

@section('content')
    @if($errors->any())
        <div
            style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
            {{ $errors->first() }}
    </div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('clientes.index') }}" class="btn btn-ghost">← Volver</a></div>

    <div class="card">
        <div class="card-header">
            <h2>Editar: {{ $cliente->razon_social }}</h2>
        </div>
        <div class="card-body">
            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST"
                data-confirm="Confirmar actualización de cliente">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Razón Social / Nombre Completo *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social', $cliente->razon_social) }}"
                            required>
                    </div>

                    <div class="form-group">
                        <label>RUC / CI</label>
                        <input type="text" name="ruc" value="{{ old('ruc', $cliente->ruc) }}">
                    </div>

                    <div class="form-group">
                        <label>Nombre de Fantasía (opcional)</label>
                        <input type="text" name="nombre_fantasia"
                            value="{{ old('nombre_fantasia', $cliente->nombre_fantasia) }}">
                    </div>

                    <div class="form-group">
                        <label>País de Residencia *</label>
                        <select name="pais" required>
                            <option value="PY" {{ old('pais', $cliente->pais) == 'PY' ? 'selected' : '' }}>Paraguay (PY)
                            </option>
                            <option value="BR" {{ old('pais', $cliente->pais) == 'BR' ? 'selected' : '' }}>Brasil (BR)
                            </option>
                            <option value="AR" {{ old('pais', $cliente->pais) == 'AR' ? 'selected' : '' }}>Argentina (AR)
                            </option>
                            <option value="BO" {{ old('pais', $cliente->pais) == 'BO' ? 'selected' : '' }}>Bolivia (BO)
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Línea de Crédito Máxima (USD)</label>
                        <input type="number" name="linea_credito_usd"
                            value="{{ old('linea_credito_usd', $cliente->linea_credito_usd) }}" step="0.01" min="0">
                        <small class="text-muted">Monto aprobado para financiamiento directo o descubierto.</small>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="activo">
                            <option value="1" {{ $cliente->activo ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ !$cliente->activo ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email', $cliente->email) }}">
                    </div>

                    <div class="form-group">
                        <label>Teléfono / WhatsApp</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}">
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label>Dirección</label>
                        <textarea name="direccion" rows="2">{{ old('direccion', $cliente->direccion) }}</textarea>
                    </div>
                </div>

                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('clientes.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
@endsection