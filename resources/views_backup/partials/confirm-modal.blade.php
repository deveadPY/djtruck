{{-- Global Confirmation Modal --}}
<div id="confirmModal"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);align-items:center;justify-content:center;z-index:99999;">
    <div
        style="background:var(--surface1);width:92%;max-width:650px;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.35);overflow:hidden;">
        <div
            style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:1.05rem" id="confirmModalTitle">✅ Confirmar datos</h3>
            <button type="button" onclick="cerrarConfirmModal()"
                style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted)">&times;</button>
        </div>
        <div style="padding:1.25rem;max-height:60vh;overflow-y:auto">
            <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem">Revisa los datos antes de guardar:
            </p>
            <div id="confirmModalBody" style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem .75rem"></div>
        </div>
        <div
            style="padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.5rem">
            <button type="button" class="btn btn-ghost" onclick="cerrarConfirmModal()">✏️ Editar</button>
            <button type="button" class="btn btn-primary" id="confirmModalSubmit">✅ Confirmar y Guardar</button>
        </div>
    </div>
</div>

<script>
    (function () {
        let pendingForm = null;

        window.cerrarConfirmModal = function () {
            document.getElementById('confirmModal').style.display = 'none';
            pendingForm = null;
        };

        document.getElementById('confirmModalSubmit').addEventListener('click', function () {
            if (pendingForm) {
                // Remove the data-confirm temporarily so we don't re-trigger
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
            document.getElementById('confirmModalTitle').textContent = '✅ ' + title;

            const body = document.getElementById('confirmModalBody');
            body.innerHTML = '';

            // Collect visible form fields
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(function (field) {
                if (field.type === 'hidden' || field.type === 'submit' || field.name === '_token' || field.name === '_method') return;
                if (field.offsetParent === null && field.type !== 'hidden') return; // invisible fields
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
                item.style.cssText = 'background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:.55rem .75rem;';
                item.innerHTML = '<div style="font-size:.65rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.15rem">'
                    + escapeHtml(label) + '</div><div style="font-size:.85rem;font-weight:500;word-break:break-word">'
                    + escapeHtml(truncate(value, 80)) + '</div>';
                body.appendChild(item);
            });

            if (body.children.length === 0) {
                body.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:1rem;color:var(--text-muted)">No se detectaron campos visibles.</div>';
            }

            document.getElementById('confirmModal').style.display = 'flex';
        });

        function findLabel(field, form) {
            // Try to find a <label> associated with the field
            if (field.id) {
                const lbl = form.querySelector('label[for="' + field.id + '"]');
                if (lbl) return lbl.textContent.trim().replace(/\*$/, '').trim();
            }
            // Try parent .form-group > label
            const group = field.closest('.form-group');
            if (group) {
                const lbl = group.querySelector('label');
                if (lbl) return lbl.textContent.trim().replace(/\*$/, '').trim();
            }
            // Fallback: humanize the name
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