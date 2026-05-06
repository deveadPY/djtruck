@extends('layouts.app')
@section('title', 'Editar Plantilla — ' . $plantilla->nombre)
@section('page-title', '✏️ Editar Plantilla: ' . $plantilla->nombre)
@include('partials.form-styles')

@push('styles')
<style>
    .var-chip {
        display:inline-flex; align-items:center;
        background:var(--surface2); border:1px solid var(--border);
        color:var(--primary); border-radius:6px;
        padding:.2rem .55rem; font-size:.72rem; font-family:monospace;
        cursor:pointer; transition:.15s; margin:.2rem;
        user-select:none;
    }
    .var-chip:hover { background:var(--primary); color:#fff; border-color:var(--primary); }

    .editor-layout {
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap:1.5rem;
        align-items:start;
    }
    @media (max-width: 900px) {
        .editor-layout { grid-template-columns: 1fr; }
    }

    .preview-frame {
        width:100%;
        min-height:500px;
        border:1px solid var(--border);
        border-radius:8px;
        background:#f5f5f5;
    }

    .sticky-preview {
        position:sticky;
        top:80px;
    }
</style>
@endpush

@section('content')

@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

<form method="POST" action="{{ route('config.email.plantilla.update', $plantilla->id) }}" id="plantillaForm">
    @csrf
    @method('PUT')

    {{-- ── Top controls ─────────────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:1rem">
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre de la plantilla</label>
                    <input type="text" name="nombre" id="nombre"
                           value="{{ old('nombre', $plantilla->nombre) }}" required>
                </div>
                <div class="form-group">
                    <label for="asunto">Asunto del correo</label>
                    <input type="text" name="asunto" id="asunto"
                           value="{{ old('asunto', $plantilla->asunto) }}" required
                           placeholder="Puede contener {{variables}}">
                    <span class="form-hint">Use <code>{{"{{"}}variable{{"}}"}}</code> para datos dinámicos</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:1rem;margin-top:.5rem">
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.85rem">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1"
                           {{ old('activo', $plantilla->activo) ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--primary)">
                    <span>Plantilla activa (se usará al enviar emails de este tipo)</span>
                </label>
            </div>
        </div>
    </div>

    {{-- ── Variables disponibles ────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:1rem">
        <div class="card-header">
            <h2 style="font-size:.9rem">Variables disponibles — Haz clic para insertar</h2>
        </div>
        <div class="card-body" style="padding:.75rem 1rem">
            @foreach($plantilla->variables_disponibles ?? [] as $var)
                <button type="button" class="var-chip" data-var="{{ $var }}">
                    {{"{{"}}{{ $var }}{{"}}"}}
                </button>
            @endforeach
            <div style="margin-top:.5rem;font-size:.72rem;color:var(--text-muted)">
                Haz clic en cualquier variable para insertarla en la posición actual del cursor en el editor.
            </div>
        </div>
    </div>

    {{-- ── Editor + Preview ─────────────────────────────────────────────── --}}
    <div class="editor-layout">
        {{-- Editor --}}
        <div class="card">
            <div class="card-header">
                <h2 style="font-size:.9rem">✏️ Editor HTML</h2>
                <span style="font-size:.72rem;color:var(--text-muted)">Puedes escribir HTML completo</span>
            </div>
            <div class="card-body" style="padding:.5rem">
                <textarea
                    name="cuerpo_html"
                    id="cuerpo_html"
                    rows="30"
                    style="width:100%;font-family:monospace;font-size:.78rem;background:var(--bg);color:var(--text);border:1px solid var(--border);border-radius:6px;padding:.75rem;resize:vertical;line-height:1.5;outline:none;"
                    spellcheck="false"
                    required
                >{{ old('cuerpo_html', $plantilla->cuerpo_html) }}</textarea>
            </div>
        </div>

        {{-- Preview --}}
        <div class="sticky-preview">
            <div class="card">
                <div class="card-header">
                    <h2 style="font-size:.9rem">👁 Vista previa en tiempo real</h2>
                    <span style="font-size:.72rem;color:var(--text-muted)">Las {{"{{"}}variables{{"}}"}} aparecerán tal cual</span>
                </div>
                <div class="card-body" style="padding:.5rem">
                    <iframe id="previewFrame" class="preview-frame" sandbox="allow-same-origin"></iframe>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Submit ────────────────────────────────────────────────────────── --}}
    <div style="display:flex;gap:.75rem;align-items:center;margin-top:1rem">
        <button type="submit" class="btn btn-primary">💾 Guardar plantilla</button>
        <a href="{{ route('config.email') }}" class="btn btn-ghost">Cancelar</a>
        <span style="font-size:.72rem;color:var(--text-muted);margin-left:auto">
            Tipo: <strong>{{ $plantilla->tipo }}</strong> · ID: {{ $plantilla->id }}
        </span>
    </div>
</form>

<script>
(function () {
    const textarea = document.getElementById('cuerpo_html');
    const iframe   = document.getElementById('previewFrame');

    /* ── Live preview ──────────────────────────────────────────────────── */
    function updatePreview() {
        try {
            iframe.srcdoc = textarea.value;
        } catch(e) {}
    }

    textarea.addEventListener('input', updatePreview);
    updatePreview(); // Initial render

    /* ── Variable chip insertion ───────────────────────────────────────── */
    document.querySelectorAll('.var-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = '{{"{{"}}' + this.dataset.var + '{{"}}"}}';
            var pos  = textarea.selectionStart;
            var end  = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, pos) + text + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = pos + text.length;
            textarea.focus();
            updatePreview();
        });
    });
}());
</script>

@endsection
