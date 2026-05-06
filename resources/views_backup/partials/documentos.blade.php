{{-- Partial: partials/documentos.blade.php
Variables requeridas:
$documentos — colección de documentos existentes
$documentableType — 'clientes', 'facturas_proveedores', 'ventas', 'vehiculos'
$documentableId — ID del registro
--}}

<div class="card" style="margin-top:1.5rem">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2>📎 Documentos Adjuntos</h2>
        <button type="button" class="btn btn-primary"
            onclick="document.getElementById('doc-upload-zone').style.display = document.getElementById('doc-upload-zone').style.display === 'none' ? 'block' : 'none'"
            style="font-size:.8rem;padding:.4rem .8rem">
            + Adjuntar
        </button>
    </div>

    {{-- Upload zone --}}
    <div id="doc-upload-zone" style="display:none;padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
        <form method="POST" action="{{ route('documentos.upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="documentable_type" value="{{ $documentableType }}">
            <input type="hidden" name="documentable_id" value="{{ $documentableId }}">

            <div id="drop-area"
                style="border:2px dashed var(--border);border-radius:10px;padding:2rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s"
                ondragover="event.preventDefault();this.style.borderColor='var(--primary)';this.style.background='rgba(99,102,241,.05)'"
                ondragleave="this.style.borderColor='var(--border)';this.style.background='transparent'"
                ondrop="event.preventDefault();this.style.borderColor='var(--border)';this.style.background='transparent';document.getElementById('doc-file-input').files=event.dataTransfer.files;updateFileList()"
                onclick="document.getElementById('doc-file-input').click()">
                <div style="font-size:2rem;margin-bottom:.5rem">📤</div>
                <div style="font-size:.85rem;color:var(--text-muted)">Arrastrá archivos aquí o hacé clic para
                    seleccionar</div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:.25rem">PDF, imágenes, Word, Excel —
                    Máx. 20MB por archivo</div>
                <input type="file" id="doc-file-input" name="archivos[]" multiple style="display:none"
                    onchange="updateFileList()"
                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar">
            </div>

            <div id="doc-file-list" style="margin-top:.75rem;display:none">
                <div
                    style="font-size:.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:.4rem">
                    Archivos seleccionados</div>
                <div id="doc-file-names" style="font-size:.82rem;color:var(--text)"></div>
            </div>

            <div style="display:flex;gap:.75rem;align-items:flex-end;margin-top:.75rem">
                <div style="flex:1">
                    <label
                        style="font-size:.72rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Descripción
                        (opcional)</label>
                    <input type="text" name="descripcion" placeholder="Ej: Contrato, Factura escaneada, Cédula..."
                        style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:.5rem .75rem;color:var(--text);font-family:inherit;font-size:.82rem;outline:none;width:100%">
                </div>
                <button type="submit" class="btn btn-primary"
                    style="font-size:.8rem;padding:.5rem 1rem;white-space:nowrap">
                    📤 Subir
                </button>
            </div>
        </form>
    </div>

    {{-- Documents list --}}
    <div class="card-body" style="padding:0">
        @if(isset($documentos) && count($documentos) > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width:40px"></th>
                        <th>Archivo</th>
                        <th>Descripción</th>
                        <th>Tamaño</th>
                        <th>Fecha</th>
                        <th style="width:120px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documentos as $doc)
                        <tr>
                            <td style="text-align:center">
                                @php
                                    $icon = match (true) {
                                        str_contains($doc->mime_type ?? '', 'pdf') => '📕',
                                        str_contains($doc->mime_type ?? '', 'image') => '🖼️',
                                        str_contains($doc->mime_type ?? '', 'word') || str_contains($doc->nombre_original ?? '', '.doc') => '📘',
                                        str_contains($doc->mime_type ?? '', 'excel') || str_contains($doc->mime_type ?? '', 'spreadsheet') || str_contains($doc->nombre_original ?? '', '.xls') => '📗',
                                        default => '📄',
                                    };
                                @endphp
                                <span style="font-size:1.2rem">{{ $icon }}</span>
                            </td>
                            <td>
                                <span style="font-weight:500;font-size:.85rem">{{ $doc->nombre_original }}</span>
                            </td>
                            <td style="color:var(--text-muted);font-size:.82rem">{{ $doc->descripcion ?? '—' }}</td>
                            <td style="font-size:.82rem;color:var(--text-muted)">
                                @if($doc->tamaño_bytes)
                                    @php
                                        $size = $doc->tamaño_bytes;
                                        if ($size >= 1048576)
                                            $sizeStr = number_format($size / 1048576, 1) . ' MB';
                                        elseif ($size >= 1024)
                                            $sizeStr = number_format($size / 1024, 0) . ' KB';
                                        else
                                            $sizeStr = $size . ' B';
                                    @endphp
                                    {{ $sizeStr }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="font-size:.82rem;color:var(--text-muted)">
                                {{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <div style="display:flex;gap:.4rem">
                                    <a href="{{ route('documentos.download', $doc->id) }}" class="btn btn-ghost"
                                        style="padding:.25rem .5rem;font-size:.72rem" title="Descargar">⬇️</a>
                                    @if(str_contains($doc->mime_type ?? '', 'image') || str_contains($doc->mime_type ?? '', 'pdf'))
                                        <a href="{{ asset($doc->ruta) }}" target="_blank" class="btn btn-ghost"
                                            style="padding:.25rem .5rem;font-size:.72rem" title="Ver">👁️</a>
                                    @endif
                                    <form method="POST" action="{{ route('documentos.destroy', $doc->id) }}"
                                        style="display:inline" data-confirm="¿Eliminar este documento?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost"
                                            style="padding:.25rem .5rem;font-size:.72rem;color:var(--danger)"
                                            title="Eliminar">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="text-align:center;color:var(--text-muted);padding:2rem;font-size:.85rem">
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