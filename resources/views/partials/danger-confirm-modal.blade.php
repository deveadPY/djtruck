{{--
    Danger Confirm Modal — para acciones destructivas (eliminar, descontinuar, anular).

    USO:
    Agregar a cualquier <form> los siguientes atributos:
      data-danger-confirm="¿Mensaje de confirmación?"
      data-danger-title="Título opcional"          (default: "Confirmar acción")
      data-danger-action-label="Etiqueta botón"     (default: "Eliminar")
      data-danger-icon="trash|warning|stop"          (default: "trash")

    Ejemplo:
      <form method="POST" action="..."
            data-danger-confirm="¿Eliminar el producto ABC?"
            data-danger-action-label="Eliminar">
          ...
      </form>
--}}
<div id="dangerConfirmModal"
     class="fixed inset-0 items-center justify-center z-[99999] hidden"
     style="background:rgba(0,0,0,.65); backdrop-filter: blur(2px);">
    <div class="w-[92%] max-w-[480px] rounded-2xl shadow-2xl overflow-hidden"
         style="background: var(--surface); border: 1px solid var(--border);">

        {{-- Header --}}
        <div class="px-6 py-5 border-b flex items-start gap-4" style="border-color: var(--border);">
            <div id="dangerConfirmIcon"
                 class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                 style="background: rgba(239,68,68,0.12); color: #ef4444;">
                {{-- Icono dinámico inyectado por JS --}}
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="dangerConfirmTitle"
                    class="text-base font-bold mb-1"
                    style="color: var(--text);">Confirmar acción</h3>
                <p id="dangerConfirmMessage"
                   class="text-sm leading-relaxed"
                   style="color: var(--text-muted);">¿Está seguro de continuar?</p>
            </div>
        </div>

        {{-- Footer con botones --}}
        <div class="px-6 py-4 flex justify-end gap-2" style="background: var(--surface2);">
            <button type="button"
                    class="btn btn-ghost"
                    onclick="cerrarDangerConfirm()">
                Cancelar
            </button>
            <button type="button"
                    id="dangerConfirmSubmit"
                    class="btn"
                    style="background: #ef4444; color: white; border-color: #dc2626;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                <span id="dangerConfirmActionLabel">Eliminar</span>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    let pendingForm = null;

    const ICONS = {
        trash:   '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>',
        warning: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>',
        stop:    '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>'
    };

    const COLOR_THEMES = {
        trash:   { bg: 'rgba(239,68,68,0.12)',  fg: '#ef4444', btn: '#ef4444', btnHover: '#dc2626' },
        warning: { bg: 'rgba(245,158,11,0.12)', fg: '#f59e0b', btn: '#f59e0b', btnHover: '#d97706' },
        stop:    { bg: 'rgba(245,158,11,0.12)', fg: '#f59e0b', btn: '#f59e0b', btnHover: '#d97706' }
    };

    window.cerrarDangerConfirm = function () {
        const modal = document.getElementById('dangerConfirmModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
        pendingForm = null;
    };

    // Cerrar con tecla ESC
    document.addEventListener('keydown', function (e) {
        const modal = document.getElementById('dangerConfirmModal');
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            cerrarDangerConfirm();
        }
    });

    // Click en backdrop cierra
    document.getElementById('dangerConfirmModal').addEventListener('click', function (e) {
        if (e.target === this) cerrarDangerConfirm();
    });

    // Submit confirmado
    document.getElementById('dangerConfirmSubmit').addEventListener('click', function () {
        if (!pendingForm) return;
        const f = pendingForm;
        pendingForm = null;
        // Remove attribute to avoid re-intercepting
        f.removeAttribute('data-danger-confirm');
        f.submit();
    });

    // Interceptar submit de cualquier form con data-danger-confirm
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.hasAttribute('data-danger-confirm')) return;

        e.preventDefault();
        pendingForm = form;

        const message     = form.getAttribute('data-danger-confirm') || '¿Está seguro de continuar?';
        const title       = form.getAttribute('data-danger-title') || 'Confirmar acción';
        const actionLabel = form.getAttribute('data-danger-action-label') || 'Eliminar';
        const iconKey     = (form.getAttribute('data-danger-icon') || 'trash').toLowerCase();

        document.getElementById('dangerConfirmTitle').textContent = title;
        document.getElementById('dangerConfirmMessage').textContent = message;
        document.getElementById('dangerConfirmActionLabel').textContent = actionLabel;

        // Icono
        const iconEl = document.getElementById('dangerConfirmIcon');
        iconEl.innerHTML = ICONS[iconKey] || ICONS.trash;

        // Tema de color (rojo para eliminar, amarillo para descontinuar/warning)
        const theme = COLOR_THEMES[iconKey] || COLOR_THEMES.trash;
        iconEl.style.background = theme.bg;
        iconEl.style.color      = theme.fg;
        const btn = document.getElementById('dangerConfirmSubmit');
        btn.style.background    = theme.btn;
        btn.style.borderColor   = theme.btnHover;

        const modal = document.getElementById('dangerConfirmModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    });
})();
</script>
