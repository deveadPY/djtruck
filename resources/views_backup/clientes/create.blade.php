@extends('layouts.app')
@section('title', 'Registrar Cliente')
@section('page-title', '👥 Registrar Cliente')

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
            <h2>Datos del cliente</h2>
        </div>
        <div class="card-body">
            <form action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data"
                data-confirm="Confirmar registro de cliente">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Razón Social / Nombre Completo *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" required autofocus>
                    </div>

                    <div class="form-group">
                        <label>RUC / CI</label>
                        <input type="text" name="ruc" value="{{ old('ruc') }}">
                    </div>

                    <div class="form-group">
                        <label>Nombre de Fantasía (opcional)</label>
                        <input type="text" name="nombre_fantasia" value="{{ old('nombre_fantasia') }}">
                    </div>

                    <div class="form-group">
                        <label>País de Residencia *</label>
                        <select name="pais" required>
                            <option value="PY" {{ old('pais', 'PY') == 'PY' ? 'selected' : '' }}>Paraguay (PY)</option>
                            <option value="BR" {{ old('pais') == 'BR' ? 'selected' : '' }}>Brasil (BR)</option>
                            <option value="AR" {{ old('pais') == 'AR' ? 'selected' : '' }}>Argentina (AR)</option>
                            <option value="BO" {{ old('pais') == 'BO' ? 'selected' : '' }}>Bolivia (BO)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Línea de Crédito Máxima (USD)</label>
                        <input type="number" name="linea_credito_usd" value="{{ old('linea_credito_usd', 0) }}" step="0.01"
                            min="0">
                        <small class="text-muted">Monto aprobado para financiamiento directo o descubierto.</small>
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}">
                    </div>

                    <div class="form-group">
                        <label>Teléfono / WhatsApp</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}">
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label>Dirección</label>
                        <textarea name="direccion" rows="2">{{ old('direccion') }}</textarea>
                    </div>
                </div>

                {{-- Documentos adjuntos --}}
                <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border)">
                    <label style="margin-bottom:.75rem;display:block">📎 Documentos (opcional)</label>
                    <div id="drop-area-create"
                        style="border:2px dashed var(--border);border-radius:10px;padding:1.5rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s"
                        ondragover="event.preventDefault();this.style.borderColor='var(--primary)';this.style.background='rgba(99,102,241,.05)'"
                        ondragleave="this.style.borderColor='var(--border)';this.style.background='transparent'"
                        ondrop="event.preventDefault();this.style.borderColor='var(--border)';this.style.background='transparent';document.getElementById('create-file-input').files=event.dataTransfer.files;showSelectedFiles()"
                        onclick="document.getElementById('create-file-input').click()">
                        <div style="font-size:1.5rem;margin-bottom:.25rem">📤</div>
                        <div style="font-size:.82rem;color:var(--text-muted)">Arrastrá archivos aquí o hacé clic para
                            seleccionar</div>
                        <div style="font-size:.7rem;color:var(--text-muted);margin-top:.15rem">Cédula, RUC, contratos, etc.
                            — Máx. 20MB por archivo</div>
                        <input type="file" id="create-file-input" name="archivos[]" multiple style="display:none"
                            onchange="showSelectedFiles()"
                            accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar">
                    </div>
                    <div id="create-file-list" style="margin-top:.5rem;display:none">
                        <div id="create-file-names" style="font-size:.82rem;color:var(--text)"></div>
                    </div>
                </div>

                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('clientes.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar cliente</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
    function showSelectedFiles() {
        const input = document.getElementById('create-file-input');
        const list = document.getElementById('create-file-list');
        const names = document.getElementById('create-file-names');
        if (input.files.length > 0) {
            list.style.display = 'block';
            let html = '';
            for (let i = 0; i < input.files.length; i++) {
                const f = input.files[i];
                const sz = f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
                html += '<div style="padding:.25rem 0;border-bottom:1px solid var(--border)">📎 ' + f.name + ' <span style="color:var(--text-muted)">(' + sz + ')</span></div>';
            }
            names.innerHTML = html;
        } else {
            list.style.display = 'none';
        }
    }
    </script>
@endpush