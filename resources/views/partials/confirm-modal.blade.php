{{-- Global Confirmation Modal --}}
<div id="confirmModal" class="fixed inset-0 items-center justify-center z-[99999] hidden"
    style="background:rgba(0,0,0,.55);">
    <div class="w-[92%] max-w-[650px] rounded-2xl shadow-2xl overflow-hidden" style="background: var(--surface);">
        <div class="px-6 py-4 border-b flex justify-between items-center" style="border-color: var(--border);">
            <h3 class="text-base font-semibold" style="color: var(--text);" id="confirmModalTitle">Confirmar datos</h3>
            <button type="button" onclick="cerrarConfirmModal()"
                class="text-2xl cursor-pointer bg-transparent border-none hover:opacity-70 transition-opacity"
                style="color: var(--text-muted);">&times;</button>
        </div>
        <div class="px-6 py-5 max-h-[60vh] overflow-y-auto">
            <p class="text-[0.82rem] mb-4" style="color: var(--text-muted);">Revisa los datos antes de guardar:</p>
            <div id="confirmModalBody" class="grid grid-cols-2 gap-2.5"></div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end gap-2" style="border-color: var(--border);">
            <button type="button" class="btn btn-ghost" onclick="cerrarConfirmModal()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                Editar
            </button>
            <button type="button" class="btn btn-primary" id="confirmModalSubmit">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Confirmar y Guardar
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        let pendingForm = null;

        window.cerrarConfirmModal = function () {
            const modal = document.getElementById('confirmModal');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            pendingForm = null;
        };

        document.getElementById('confirmModalSubmit').addEventListener('click', function () {
            if (pendingForm) {
                const attr = pendingForm.getAttribute('data-confirm');
                pendingForm.removeAttribute('data-confirm');
                pendingForm.submit();
                pendingForm.setAttribute('data-confirm', attr);
            }
        });

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.hasAttribute('data-confirm')) return;

            e.preventDefault();
            pendingForm = form;

            const title = form.getAttribute('data-confirm') || 'Confirmar datos';
            document.getElementById('confirmModalTitle').textContent = title;

            const body = document.getElementById('confirmModalBody');
            body.innerHTML = '';

            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(function (field) {
                if (field.type === 'hidden' || field.type === 'submit' || field.name === '_token' || field.name === '_method') return;
                if (field.offsetParent === null && field.type !== 'hidden') return;
                if (!field.name) return;

                const label = findLabel(field, form);
                let value = '';

                if (field.tagName === 'SELECT') {
                    const opt = field.options[field.selectedIndex];
                    value = opt ? opt.textContent.trim() : '';
                } else if (field.type === 'checkbox') {
                    value = field.checked ? 'Sí' : 'No';
                } else {
                    value = field.value;
                }

                if (!value && value !== '0') value = '—';

                const item = document.createElement('div');
                item.className = 'rounded-lg p-3 border';
                item.style.background = 'var(--surface2)';
                item.style.borderColor = 'var(--border)';
                item.innerHTML = '<div class="text-[0.65rem] font-semibold uppercase tracking-wider mb-0.5" style="color:var(--text-muted)">'
                    + escapeHtml(label) + '</div><div class="text-[0.85rem] font-medium break-words" style="color:var(--text)">'
                    + escapeHtml(truncate(value, 80)) + '</div>';
                body.appendChild(item);
            });

            if (body.children.length === 0) {
                body.innerHTML = '<div class="col-span-2 text-center py-4" style="color:var(--text-muted)">No se detectaron campos visibles.</div>';
            }

            const modal = document.getElementById('confirmModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        });

        function findLabel(field, form) {
            if (field.id) {
                const lbl = form.querySelector('label[for="' + field.id + '"]');
                if (lbl) return lbl.textContent.trim().replace(/\*$/, '').trim();
            }
            const group = field.closest('.form-group');
            if (group) {
                const lbl = group.querySelector('label');
                if (lbl) return lbl.textContent.trim().replace(/\*$/, '').trim();
            }
            return humanize(field.name);
        }

        function humanize(str) {
            return str.replace(/_/g, ' ').replace(/\[.*\]/g, '').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function truncate(str, max) {
            return str.length > max ? str.substring(0, max) + '…' : str;
        }
    })();
</script>