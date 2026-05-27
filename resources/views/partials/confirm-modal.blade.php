{{--
    Confirm Modal — versión simplificada y robusta.

    USO:
    Agregar a cualquier <form> el atributo:
      data-confirm="Mensaje de confirmación"

    Opcionalmente:
      data-confirm-action-label="Texto del botón confirmar"  (default: "Confirmar y Guardar")
      data-confirm-cancel-label="Texto del botón cancelar"   (default: "Cancelar")

    Ejemplo:
      <form method="POST" action="..." data-confirm="¿Actualizar este vehículo?">
          ...
      </form>
--}}
<div id="confirmModal"
     class="hidden"
     style="position:fixed !important; top:0 !important; left:0 !important; right:0 !important; bottom:0 !important; z-index:2147483647 !important; background:rgba(0,0,0,.65) !important; backdrop-filter:blur(2px); align-items:center; justify-content:center;">
    <div class="w-[92%] max-w-[480px] rounded-2xl shadow-2xl overflow-hidden"
         style="background: var(--surface); border: 1px solid var(--border);">

        {{-- Header --}}
        <div class="px-6 py-5 border-b flex items-start gap-4" style="border-color: var(--border);">
            <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                 style="background: rgba(99,102,241,0.12); color: #6366f1;">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="confirmModalTitle"
                    class="text-base font-bold mb-1"
                    style="color: var(--text);">Confirmar acción</h3>
                <p id="confirmModalMessage"
                   class="text-sm leading-relaxed"
                   style="color: var(--text-muted);">¿Está seguro de continuar?</p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 flex justify-end gap-2" style="background: var(--surface2);">
            <button type="button"
                    class="btn btn-ghost"
                    onclick="cerrarConfirmModal()">
                <span id="confirmModalCancelLabel">Cancelar</span>
            </button>
            <button type="button"
                    id="confirmModalSubmit"
                    class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span id="confirmModalActionLabel">Confirmar y Guardar</span>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    let pendingForm = null;

    window.cerrarConfirmModal = function () {
        const modal = document.getElementById('confirmModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.style.display = 'none';
        pendingForm = null;
    };

    // Cerrar con tecla ESC
    document.addEventListener('keydown', function (e) {
        const modal = document.getElementById('confirmModal');
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            cerrarConfirmModal();
        }
    });

    // Click en backdrop cierra
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) cerrarConfirmModal();
            });
        }

        const submitBtn = document.getElementById('confirmModalSubmit');
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                if (!pendingForm) return;
                const f = pendingForm;
                pendingForm = null;
                // Quitar el atributo para evitar interceptar de nuevo
                f.removeAttribute('data-confirm');
                f.submit();
            });
        }
    });

    // Interceptar submit de cualquier form con data-confirm
    document.addEventListener('submit', function (e) {
        try {
            const form = e.target;
            if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm')) return;

            e.preventDefault();
            pendingForm = form;

            const message      = form.getAttribute('data-confirm') || '¿Está seguro de continuar?';
            const actionLabel  = form.getAttribute('data-confirm-action-label') || 'Confirmar y Guardar';
            const cancelLabel  = form.getAttribute('data-confirm-cancel-label') || 'Cancelar';

            const titleEl  = document.getElementById('confirmModalTitle');
            const msgEl    = document.getElementById('confirmModalMessage');
            const actionEl = document.getElementById('confirmModalActionLabel');
            const cancelEl = document.getElementById('confirmModalCancelLabel');

            if (titleEl)  titleEl.textContent  = 'Confirmar acción';
            if (msgEl)    msgEl.textContent    = message;
            if (actionEl) actionEl.textContent = actionLabel;
            if (cancelEl) cancelEl.textContent = cancelLabel;

            const modal = document.getElementById('confirmModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.setProperty('display', 'flex', 'important');
            } else {
                // Fallback: si no se encuentra el modal, enviar directamente
                console.warn('confirmModal not found, submitting directly');
                form.removeAttribute('data-confirm');
                form.submit();
            }
        } catch (err) {
            // Si algo falla, no bloquear el form — enviar directamente
            console.error('Error en confirm-modal:', err);
            if (pendingForm) {
                pendingForm.removeAttribute('data-confirm');
                pendingForm.submit();
            }
        }
    });
})();
</script>
