@extends('layouts.app')
@section('title', 'Editar Cliente')
@section('page-title', 'Editar Cliente')

@section('content')
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>
    @endif

    <div class="mb-4"><a href="{{ route('clientes.index') }}" class="btn btn-ghost"><svg class="w-4 h-4" fill="none"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg> Volver</a></div>

    <div class="erp-card">
        <div class="erp-card-header">
            <h2>Editar: {{ $cliente->razon_social }}</h2>
        </div>
        <div class="erp-card-body">
            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST"
                data-confirm="Confirmar actualización de cliente">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Razón Social / Nombre Completo *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social', $cliente->razon_social) }}"
                            required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">RUC / CI</label>
                        <input type="text" name="ruc" value="{{ old('ruc', $cliente->ruc) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre de Fantasía (opcional)</label>
                        <input type="text" name="nombre_fantasia"
                            value="{{ old('nombre_fantasia', $cliente->nombre_fantasia) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">País de Residencia *</label>
                        <select name="pais" required class="form-input">
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
                        <label class="form-label">Línea de Crédito Máxima (USD)</label>
                        <input type="number" name="linea_credito_usd"
                            value="{{ old('linea_credito_usd', $cliente->linea_credito_usd) }}" step="0.01" min="0"
                            class="form-input">
                        <small class="text-xs" style="color:var(--text-muted)">Monto aprobado para financiamiento.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select name="activo" class="form-input">
                            <option value="1" {{ $cliente->activo ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ !$cliente->activo ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email', $cliente->email) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono / WhatsApp</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                            class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Dirección</label>
                        <textarea name="direccion" rows="2"
                            class="form-input">{{ old('direccion', $cliente->direccion) }}</textarea>
                    </div>
                </div>
                <div class="flex gap-3 justify-end pt-5 mt-4 border-t" style="border-color:var(--border)">
                    <a href="{{ route('clientes.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
@endsection