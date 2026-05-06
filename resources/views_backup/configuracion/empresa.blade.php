@extends('layouts.app')

@section('title', 'Configuración de Empresa')
@section('page-title', '⚙️ Configuración de Empresa')

@include('partials.form-styles')

@push('styles')
<style>
    .config-section { margin-bottom: 1.75rem; }

    .section-header {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border);
    }
    .section-header h3 { margin: 0; font-size: .95rem; font-weight: 600; }
    .section-icon {
        width: 32px; height: 32px; border-radius: 8px;
        background: rgba(108,99,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .section-body { padding: 1.5rem; }

    .logo-preview-box { display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap; }
    .logo-current { display: flex; flex-direction: column; gap: .75rem; }
    .logo-img-wrap {
        background: #fff; border: 2px solid var(--border); border-radius: 12px;
        padding: 12px 16px; display: inline-flex; align-items: center;
        justify-content: center; min-width: 140px; min-height: 80px;
    }
    .logo-img-wrap img { max-height: 70px; max-width: 180px; object-fit: contain; }
    .logo-placeholder {
        background: var(--surface2); border: 2px dashed var(--border);
        border-radius: 12px; padding: 20px 28px;
        color: var(--text-muted); font-size: .85rem; text-align: center;
        min-width: 140px;
    }
    .file-upload-area { flex: 1; min-width: 260px; }
    .file-drop-zone {
        position: relative; border: 2px dashed var(--border);
        border-radius: 12px; padding: 1.5rem;
        text-align: center; cursor: pointer;
        transition: border-color .2s, background .2s;
        background: var(--surface2);
    }
    .file-drop-zone:hover { border-color: var(--primary); background: rgba(108,99,255,.05); }
    .file-drop-zone input[type="file"] {
        position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .file-drop-zone .drop-icon { font-size: 2rem; margin-bottom: .5rem; }
    .file-drop-zone .drop-text { font-size: .85rem; color: var(--text); }
    .file-drop-zone .drop-hint { font-size: .75rem; color: var(--text-muted); margin-top: .25rem; }
    #logoPreviewNew {
        display: none; margin-top: .75rem; border-radius: 10px;
        background: #fff; padding: 8px; max-height: 80px;
        border: 1px solid var(--border);
    }
    .btn-danger-sm {
        background: transparent; border: 1px solid var(--danger); color: var(--danger);
        padding: .3rem .8rem; border-radius: 6px; font-size: .78rem;
        cursor: pointer; transition: .2s;
    }
    .btn-danger-sm:hover { background: rgba(239,68,68,.1); }
    .field-hint { font-size: .75rem; color: var(--text-muted); margin-top: .2rem; display: block; }
    .form-footer {
        display: flex; justify-content: flex-end; gap: 1rem;
        padding: 1.25rem 1.5rem; border-top: 1px solid var(--border);
        background: var(--surface); border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }
    .btn-cancel {
        background: var(--surface2); color: var(--text-muted);
        padding: .55rem 1.4rem; border-radius: 8px; text-decoration: none;
        font-size: .875rem; font-weight: 600; border: 1px solid var(--border);
        cursor: pointer;
    }
    .btn-cancel:hover { color: var(--text); background: var(--surface); }
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .form-group.full { grid-column: auto; }
    }
</style>
@endpush

@section('content')
<div style="max-width: 920px; margin: 0 auto;">

    @if(session('success'))
    <div class="flash-success" style="margin-bottom: 1.25rem;">✅ {{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="flash-error" style="margin-bottom: 1.25rem;">
        <strong>Corregí los siguientes errores:</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('config.update') }}" enctype="multipart/form-data">
        @csrf

        {{-- ── DATOS DE LA EMPRESA ─────────────────────────────────────── --}}
        <div class="card config-section">
            <div class="section-header">
                <div class="section-icon">🏢</div>
                <h3>Datos de la Empresa</h3>
            </div>
            <div class="section-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nombre de la Empresa *</label>
                        <input type="text" name="nombre_empresa"
                               value="{{ old('nombre_empresa', $empresa?->nombre_empresa) }}"
                               placeholder="Ej: Trans & Logística SA" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>RUC / NIT</label>
                        <input type="text" name="ruc"
                               value="{{ old('ruc', $empresa?->ruc) }}"
                               placeholder="80000000-0">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono"
                               value="{{ old('telefono', $empresa?->telefono) }}"
                               placeholder="+595 21 123456">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email"
                               value="{{ old('email', $empresa?->email) }}"
                               placeholder="info@miempresa.com">
                    </div>
                    <div class="form-group">
                        <label>Sitio Web</label>
                        <input type="url" name="sitio_web"
                               value="{{ old('sitio_web', $empresa?->sitio_web) }}"
                               placeholder="https://miempresa.com">
                    </div>
                    <div class="form-group full">
                        <label>Dirección</label>
                        <input type="text" name="direccion"
                               value="{{ old('direccion', $empresa?->direccion) }}"
                               placeholder="Av. Principal 1234, Of. 5">
                    </div>
                    <div class="form-group">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad"
                               value="{{ old('ciudad', $empresa?->ciudad) }}"
                               placeholder="Asunción">
                    </div>
                    <div class="form-group">
                        <label>País</label>
                        <input type="text" name="pais"
                               value="{{ old('pais', $empresa?->pais ?? 'Paraguay') }}"
                               placeholder="Paraguay">
                    </div>
                    <div class="form-group">
                        <label>Moneda Base</label>
                        <select name="moneda_base">
                            <option value="USD" @selected(old('moneda_base', $empresa?->moneda_base) === 'USD')>USD — Dólar Americano</option>
                            <option value="PYG" @selected(old('moneda_base', $empresa?->moneda_base) === 'PYG')>PYG — Guaraní Paraguayo</option>
                            <option value="BRL" @selected(old('moneda_base', $empresa?->moneda_base) === 'BRL')>BRL — Real Brasileño</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── LOGO ────────────────────────────────────────────────────── --}}
        <div class="card config-section">
            <div class="section-header">
                <div class="section-icon">🖼️</div>
                <h3>Logo de la Empresa</h3>
            </div>
            <div class="section-body">
                <div class="logo-preview-box">
                    <div class="logo-current">
                        <span class="field-hint" style="margin-bottom:.4rem;">Logo actual</span>
                        @if($empresa?->logo_path)
                            <div class="logo-img-wrap">
                                <img src="{{ $empresa->logoUrl() }}" alt="Logo actual">
                            </div>
                            <form method="POST" action="{{ route('config.logo.destroy') }}"
                                  onsubmit="return confirm('¿Eliminar el logo actual?')" style="margin-top:.25rem;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger-sm">🗑 Eliminar logo</button>
                            </form>
                        @else
                            <div class="logo-placeholder">
                                <div style="font-size:2rem;margin-bottom:.4rem;">🏢</div>
                                <div>Sin logo cargado</div>
                            </div>
                        @endif
                    </div>

                    <div class="file-upload-area">
                        <span class="field-hint" style="margin-bottom:.6rem;">{{ $empresa?->logo_path ? 'Reemplazar logo' : 'Subir logo' }}</span>
                        <div class="file-drop-zone" id="dropZone">
                            <input type="file" name="logo" id="logoInput"
                                   accept="image/png,image/jpeg,image/svg+xml">
                            <div class="drop-icon">📁</div>
                            <div class="drop-text">Clic o arrastrá tu logo aquí</div>
                            <div class="drop-hint">PNG, JPG, SVG — máx. 2 MB</div>
                        </div>
                        <img id="logoPreviewNew" alt="Vista previa">
                        <span class="field-hint" style="margin-top:.5rem;">
                            El logo aparecerá en el sidebar y en todos los PDFs generados.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── NUMERACIÓN Y TIMBRADO ───────────────────────────────────── --}}
        <div class="card config-section">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <h3>Numeración de Documentos y Timbrado SIFEN</h3>
            </div>
            <div class="section-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Prefijo de Ventas</label>
                        <input type="text" name="prefijo_venta" maxlength="5"
                               value="{{ old('prefijo_venta', $empresa?->prefijo_venta ?? 'V') }}"
                               placeholder="V">
                        <span class="field-hint">Ej: <strong style="color:var(--text);">V</strong> → V-202603-0001</span>
                    </div>
                    <div class="form-group">
                        <label>Prefijo de Facturas</label>
                        <input type="text" name="prefijo_factura" maxlength="5"
                               value="{{ old('prefijo_factura', $empresa?->prefijo_factura ?? 'F') }}"
                               placeholder="F">
                        <span class="field-hint">Ej: <strong style="color:var(--text);">F</strong> → F-202603-0001</span>
                    </div>
                    <div class="form-group">
                        <label>Número de Timbrado</label>
                        <input type="text" name="timbrado" maxlength="20"
                               value="{{ old('timbrado', $empresa?->timbrado) }}"
                               placeholder="12345678">
                        <span class="field-hint">Número otorgado por la SET / SIFEN</span>
                    </div>
                    <div class="form-group">
                        <label>Vigencia del Timbrado</label>
                        <input type="date" name="vigencia_timbrado"
                               value="{{ old('vigencia_timbrado', $empresa?->vigencia_timbrado?->format('Y-m-d')) }}">
                        <span class="field-hint">Fecha de vencimiento del timbrado</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="form-footer">
            <a href="{{ route('dashboard') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding:.55rem 1.8rem;">
                💾 Guardar Configuración
            </button>
        </div>

    </form>
</div>

<script>
document.getElementById('logoInput').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;
    const preview = document.getElementById('logoPreviewNew');
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
    document.querySelector('#dropZone .drop-text').textContent = file.name;
    document.querySelector('#dropZone .drop-hint').textContent =
        (file.size / 1024).toFixed(1) + ' KB';
});
const dz = document.getElementById('dropZone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor = 'var(--primary)'; });
dz.addEventListener('dragleave', () => { dz.style.borderColor = ''; });
dz.addEventListener('drop', () => { dz.style.borderColor = ''; });
</script>
@endsection
