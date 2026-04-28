{{-- Partial: partials/documentos.blade.php
Variables requeridas:
$documentos — colección de documentos existentes
$documentableType — 'clientes', 'facturas_proveedores', 'ventas', 'vehiculos'
$documentableId — ID del registro
--}}

<div class="erp-card mt-6">
    <div class="erp-card-header">
        <h2 class="flex items-center gap-2">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
            </svg>
            Documentos Adjuntos
        </h2>
        <button type="button" class="btn btn-primary text-xs"
            onclick="document.getElementById('doc-upload-zone').style.display = document.getElementById('doc-upload-zone').style.display === 'none' ? 'block' : 'none'">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Adjuntar
        </button>
    </div>

    {{-- Upload zone --}}
    <div id="doc-upload-zone" class="hidden p-4 border-b" style="border-color:var(--border)">
        <form method="POST" action="{{ route('documentos.upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="documentable_type" value="{{ $documentableType }}">
            <input type="hidden" name="documentable_id" value="{{ $documentableId }}">

            {{-- Selector de tipo si el contexto lo provee --}}
            @isset($tiposDocumento)
            <div class="flex gap-3 mb-3 flex-wrap">
                <div class="flex-1" style="min-width:160px">
                    <label class="form-label">Tipo de Documento</label>
                    <select name="tipo" class="form-input">
                        <option value="">— Sin tipo —</option>
                        @foreach($tiposDocumento as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endisset

            <div id="drop-area"
                class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200 hover:border-primary"
                style="border-color:var(--border);color:var(--text-muted)"
                ondragover="event.preventDefault();this.style.borderColor='#6c63ff';this.style.background='rgba(99,102,241,.05)'"
                ondragleave="this.style.borderColor='var(--border)';this.style.background='transparent'"
                ondrop="event.preventDefault();this.style.borderColor='var(--border)';this.style.background='transparent';document.getElementById('doc-file-input').files=event.dataTransfer.files;updateFileList()"
                onclick="document.getElementById('doc-file-input').click()">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                <div class="text-sm">Arrastrá archivos aquí o hacé clic para seleccionar</div>
                <div class="text-xs mt-1">PDF, imágenes, Word, Excel — Máx. 20MB por archivo</div>
                <input type="file" id="doc-file-input" name="archivos[]" multiple class="hidden"
                    onchange="updateFileList()"
                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar">
            </div>

            <div id="doc-file-list" class="mt-3 hidden">
                <div class="text-xs font-semibold uppercase mb-1" style="color:var(--text-muted)">Archivos seleccionados
                </div>
                <div id="doc-file-names" class="text-sm"></div>
            </div>

            <div class="flex gap-3 items-end mt-3">
                <div class="flex-1">
                    <label class="form-label">Descripción (opcional)</label>
                    <input type="text" name="descripcion" placeholder="Ej: Contrato, Factura escaneada, Cédula..."
                        class="form-input">
                </div>
                <button type="submit" class="btn btn-primary whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    Subir
                </button>
            </div>
        </form>
    </div>

    {{-- Documents list --}}
    <div class="overflow-x-auto">
        @if(isset($documentos) && count($documentos) > 0)
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="w-10"></th>
                        <th>Archivo</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Tamaño</th>
                        <th>Fecha</th>
                        <th class="w-28">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documentos as $doc)
                        <tr>
                            <td class="text-center">
                                @php
                                    $iconSvg = match (true) {
                                        str_contains($doc->mime_type ?? '', 'pdf') => '<svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>',
                                        str_contains($doc->mime_type ?? '', 'image') => '<svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>',
                                        default => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="color:var(--text-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>',
                                    };
                                @endphp
                                {!! $iconSvg !!}
                            </td>
                            <td><span class="font-medium text-sm">{{ $doc->nombre_original }}</span></td>
                            <td class="text-xs">
                                @if(!empty($doc->tipo))
                                    @php $tipoLabels = ['CI'=>'CI','RUC_IVA'=>'RUC/IVA','UBICACION'=>'Ubicación','CONTRATO'=>'Contrato','OTRO'=>'Otro']; @endphp
                                    <span class="badge-status badge-preparacion">{{ $tipoLabels[$doc->tipo] ?? $doc->tipo }}</span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td class="text-xs" style="color:var(--text-muted)">{{ $doc->descripcion ?? '—' }}</td>
                            <td class="text-xs" style="color:var(--text-muted)">
                                @if($doc->tamano_bytes)
                                    @php
                                        $size = $doc->tamano_bytes;
                                        $sizeStr = $size >= 1048576 ? number_format($size / 1048576, 1) . ' MB' : ($size >= 1024 ? number_format($size / 1024, 0) . ' KB' : $size . ' B');
                                    @endphp
                                    {{ $sizeStr }}
                                @else —
                                @endif
                            </td>
                            <td class="text-xs" style="color:var(--text-muted)">
                                {{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="{{ route('documentos.download', $doc->id) }}"
                                        class="btn btn-ghost text-xs px-2 py-1" title="Descargar">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                    </a>
                                    @if(str_contains($doc->mime_type ?? '', 'image') || str_contains($doc->mime_type ?? '', 'pdf'))
                                        <a href="{{ asset($doc->ruta) }}" target="_blank" class="btn btn-ghost text-xs px-2 py-1"
                                            title="Ver">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('documentos.destroy', $doc->id) }}" class="inline"
                                        data-confirm="¿Eliminar este documento?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost text-xs px-2 py-1 text-red-500"
                                            title="Eliminar">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-8 text-sm" style="color:var(--text-muted)">
                Sin documentos adjuntos. Hacé clic en <strong>+ Adjuntar</strong> para cargar archivos.
            </div>
        @endif
    </div>
</div>

<script>
    function updateFileList() {
        const input = document.getElementById('doc-file-input');
        const list = document.getElementById('doc-file-list');
        const names = document.getElementById('doc-file-names');
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