{{--
    Modal de creación rápida de proveedor.
    Llama a route('proveedores.quickStore') vía fetch JSON.
    Al éxito: agrega <option> al select #proveedor_id y la selecciona.
--}}
<div id="quickProveedorModal"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:flex-start; justify-content:center; padding:3rem 1rem; backdrop-filter: blur(2px);">

    <div style="background:var(--surface); color:var(--text); border:1px solid var(--border); border-radius:14px; width:100%; max-width:560px; box-shadow:var(--shadow-lg); overflow:hidden;">

        <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-size:1.05rem; font-weight:600; color:var(--text);">
                ✚ Nuevo proveedor
            </h3>
            <button type="button" id="btnCloseProveedorModal"
                    style="background:transparent; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer; line-height:1; padding:0 .25rem;">
                ×
            </button>
        </div>

        <div style="padding:1.25rem;">
            <div id="qpAlert" style="display:none; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.9rem;"></div>

            <form id="quickProveedorForm" novalidate>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:.85rem;">
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            Razón social <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="text" name="razon_social" required maxlength="200"
                               placeholder="Ej: Volvo do Brasil SA"
                               class="form-input" style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            RUC / RUT / NIT
                        </label>
                        <input type="text" name="ruc_rut_nit" maxlength="30"
                               placeholder="80012345-6"
                               class="form-input" style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            Nombre fantasía
                        </label>
                        <input type="text" name="nombre_fantasia" maxlength="200"
                               placeholder="Volvo"
                               class="form-input" style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            Tipo
                        </label>
                        <select name="tipo" class="form-input" style="width:100%;">
                            <option value="DISTRIBUIDOR">Distribuidor</option>
                            <option value="FABRICANTE">Fabricante</option>
                            <option value="IMPORTADOR">Importador</option>
                            <option value="SERVICIO">Servicio</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            País
                        </label>
                        <select name="pais" class="form-input" style="width:100%;">
                            <option value="PY" selected>Paraguay</option>
                            <option value="BR">Brasil</option>
                            <option value="AR">Argentina</option>
                            <option value="CL">Chile</option>
                            <option value="UY">Uruguay</option>
                            <option value="US">Estados Unidos</option>
                            <option value="CN">China</option>
                            <option value="JP">Japón</option>
                            <option value="DE">Alemania</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            Moneda
                        </label>
                        <select name="moneda_principal" class="form-input" style="width:100%;">
                            <option value="USD" selected>USD</option>
                            <option value="PYG">PYG</option>
                            <option value="BRL">BRL</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            Email
                        </label>
                        <input type="email" name="email" maxlength="150"
                               placeholder="ventas@proveedor.com"
                               class="form-input" style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.3rem;">
                            Teléfono
                        </label>
                        <input type="text" name="telefono" maxlength="50"
                               placeholder="+595 21 555-1234"
                               class="form-input" style="width:100%;">
                    </div>
                </div>
            </form>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.25rem; border-top:1px solid var(--border); background:var(--surface2);">
            <button type="button" id="btnCancelProveedorModal"
                    style="padding:.6rem 1.1rem; background:transparent; color:var(--text); border:1px solid var(--border); border-radius:8px; cursor:pointer; font-weight:500;">
                Cancelar
            </button>
            <button type="button" id="btnSubmitProveedorModal"
                    style="padding:.6rem 1.4rem; background:var(--primary); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600;">
                Guardar proveedor
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modal      = document.getElementById('quickProveedorModal');
    const btnOpen    = document.getElementById('btnOpenProveedorModal');
    const btnClose   = document.getElementById('btnCloseProveedorModal');
    const btnCancel  = document.getElementById('btnCancelProveedorModal');
    const btnSubmit  = document.getElementById('btnSubmitProveedorModal');
    const form       = document.getElementById('quickProveedorForm');
    const alertBox   = document.getElementById('qpAlert');
    const selectProv = document.getElementById('proveedor_id');

    if (!modal || !btnOpen) return; // partial no cargado

    const url   = "{{ route('proveedores.quickStore') }}";
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    function open()  { modal.style.display = 'flex';  form.querySelector('[name="razon_social"]').focus(); }
    function close() { modal.style.display = 'none';  form.reset();  hideAlert(); }

    function showAlert(msg, type = 'error') {
        alertBox.textContent = msg;
        alertBox.style.display = 'block';
        if (type === 'error') {
            alertBox.style.background = 'rgba(220,38,38,.12)';
            alertBox.style.color = '#dc2626';
            alertBox.style.border = '1px solid rgba(220,38,38,.3)';
        } else {
            alertBox.style.background = 'rgba(34,197,94,.12)';
            alertBox.style.color = '#16a34a';
            alertBox.style.border = '1px solid rgba(34,197,94,.3)';
        }
    }
    function hideAlert() { alertBox.style.display = 'none'; }

    btnOpen.addEventListener('click', open);
    btnClose.addEventListener('click', close);
    btnCancel.addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.style.display === 'flex') close(); });

    btnSubmit.addEventListener('click', async () => {
        hideAlert();

        const razon = form.querySelector('[name="razon_social"]').value.trim();
        if (!razon) {
            showAlert('La razón social es obligatoria.');
            form.querySelector('[name="razon_social"]').focus();
            return;
        }

        btnSubmit.disabled = true;
        const original = btnSubmit.textContent;
        btnSubmit.textContent = 'Guardando…';

        try {
            const formData = new FormData(form);
            const payload  = Object.fromEntries(formData.entries());

            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin',
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok || !data.success) {
                let msg = data.message || 'No se pudo crear el proveedor.';
                if (data.errors) {
                    const firsts = Object.values(data.errors).flat().slice(0, 3);
                    if (firsts.length) msg = firsts.join(' · ');
                }
                showAlert(msg);
                return;
            }

            // Éxito: insertar y seleccionar
            const p = data.proveedor;
            const opt = document.createElement('option');
            opt.value       = p.id;
            opt.textContent = p.razon_social + (p.ruc_rut_nit ? ' (' + p.ruc_rut_nit + ')' : '');
            opt.selected    = true;
            selectProv.appendChild(opt);
            selectProv.value = p.id;

            showAlert('✓ Proveedor creado. Ya quedó seleccionado.', 'success');
            setTimeout(close, 900);
        } catch (err) {
            showAlert('Error de conexión: ' + (err.message || err));
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = original;
        }
    });
})();
</script>
@endpush
