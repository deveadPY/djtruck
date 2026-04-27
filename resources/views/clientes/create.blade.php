@extends('layouts.app')
@section('title', 'Registrar Cliente')
@section('page-title', 'Registrar Cliente')

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
            <h2>Datos del cliente</h2>
        </div>
        <div class="erp-card-body">
            <form action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data"
                data-confirm="Confirmar registro de cliente">
                @csrf
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Razón Social / Nombre Completo *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" required autofocus
                            class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">RUC / CI</label>
                        <input type="text" name="ruc" value="{{ old('ruc') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre de Fantasía (opcional)</label>
                        <input type="text" name="nombre_fantasia" value="{{ old('nombre_fantasia') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">País de Residencia *</label>
                        <select name="pais" required class="form-input">
                            <option value="PY" {{ old('pais', 'PY') == 'PY' ? 'selected' : '' }}>Paraguay (PY)</option>
                            <option value="BR" {{ old('pais') == 'BR' ? 'selected' : '' }}>Brasil (BR)</option>
                            <option value="AR" {{ old('pais') == 'AR' ? 'selected' : '' }}>Argentina (AR)</option>
                            <option value="BO" {{ old('pais') == 'BO' ? 'selected' : '' }}>Bolivia (BO)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Línea de Crédito Máxima (USD)</label>
                        <input type="number" name="linea_credito_usd" value="{{ old('linea_credito_usd', 0) }}" step="0.01"
                            min="0" class="form-input">
                        <small class="text-xs" style="color:var(--text-muted)">Monto aprobado para financiamiento
                            directo.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono / WhatsApp</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}" class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Dirección</label>
                        <textarea name="direccion" rows="2" class="form-input">{{ old('direccion') }}</textarea>
                    </div>
                </div>

                {{-- Documentos adjuntos --}}
                <div class="mt-6 pt-5 border-t" style="border-color:var(--border)">
                    <label class="form-label mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                        Documentos (opcional)
                    </label>
                    <div id="drop-area-create"
                        class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all duration-200 hover:border-primary"
                        style="border-color:var(--border);color:var(--text-muted)"
                        ondragover="event.preventDefault();this.style.borderColor='#6c63ff';this.style.background='rgba(99,102,241,.05)'"
                        ondragleave="this.style.borderColor='var(--border)';this.style.background='transparent'"
                        ondrop="event.preventDefault();this.style.borderColor='var(--border)';this.style.background='transparent';document.getElementById('create-file-input').files=event.dataTransfer.files;showSelectedFiles()"
                        onclick="document.getElementById('create-file-input').click()">
                        <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <div class="text-sm">Arrastrá archivos aquí o hacé clic para seleccionar</div>
                        <div class="text-xs mt-1">Cédula, RUC, contratos — Máx. 20MB</div>
                        <input type="file" id="create-file-input" name="archivos[]" multiple class="hidden"
                            onchange="showSelectedFiles()"
                            accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar">
                    </div>
                    <div id="create-file-list" class="mt-2 hidden">
                        <div id="create-file-names" class="text-sm"></div>
                    </div>
                </div>

                <div class="flex gap-3 justify-end pt-5 mt-4 border-t" style="border-color:var(--border)">
                    <a href="{{ route('clientes.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg> Guardar cliente</button>
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
                list.classList.remove('hidden');
                let html = '';
                for (let i = 0; i < input.files.length; i++) {
                    const f = input.files[i];
                    const sz = f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
                    html += '<div class="py-1 border-b text-sm" style="border-color:var(--border)"><svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg> ' + f.name + ' <span style="color:var(--text-muted)">(' + sz + ')</span></div>';
                }
                names.innerHTML = html;
            } else {
                list.classList.add('hidden');
            }
        }
    </script>
@endpush