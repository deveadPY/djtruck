@can('cuotas.ver')
<script>
(function () {
    'use strict';

    const POLL_MS    = 120000;
    const URL_FETCH  = '{{ route("api.notificaciones") }}';
    const URL_DESC   = '{{ route("api.notificaciones.descartar") }}';
    const URL_DESC_T = '{{ route("api.notificaciones.descartar-todas") }}';
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let lastData = null;
    let isOpen   = false;

    /* ── POST helper ─────────────────────────────────────────────────────── */
    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify(body),
        }).then(function (r) { return r.ok ? r.json() : null; });
    }

    /* ── Format helpers ──────────────────────────────────────────────────── */
    function fmt(capital, interes, moneda) {
        const total = parseFloat(capital || 0) + parseFloat(interes || 0);
        return (moneda || 'USD') + ' ' + total.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtRaw(monto, moneda) {
        return (moneda || 'USD') + ' ' + parseFloat(monto || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtDate(d) {
        if (!d) return '';
        const [y, m, day] = d.split('-');
        return (day || '?') + '/' + (m || '?') + '/' + (y || '?');
    }

    /* ── Slide-out & server dismiss ─────────────────────────────────────── */
    window.dismissNotifItem = function (tipo, refId, e) {
        e.preventDefault();
        e.stopPropagation();

        // Animación inmediata
        const row = document.getElementById('nrow_' + tipo + '_' + refId);
        if (row) {
            row.style.transition = 'opacity .2s ease, transform .2s ease, max-height .25s ease, margin .25s ease, padding .25s ease';
            row.style.opacity    = '0';
            row.style.transform  = 'translateX(12px)';
            row.style.maxHeight  = '0';
            row.style.overflow   = 'hidden';
            row.style.marginBottom = '0';
            row.style.padding    = '0';
            setTimeout(function () { row.remove(); recheckEmpty(); updateBadgeFromDOM(); }, 260);
        }

        // Persistir en servidor
        postJson(URL_DESC, { tipo: tipo, referencia_id: refId });
    };

    /* ── Limpiar todas → POST al servidor ───────────────────────────────── */
    window.clearAllNotifs = function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!lastData) return;

        const items = [];
        (lastData.cuotas_mora     || []).forEach(function (i) { items.push({ tipo: 'mora',     referencia_id: i.cuota_id   }); });
        (lastData.cuotas_hoy      || []).forEach(function (i) { items.push({ tipo: 'hoy',      referencia_id: i.cuota_id   }); });
        (lastData.cuotas_proximas || []).forEach(function (i) { items.push({ tipo: 'prox',     referencia_id: i.cuota_id   }); });
        (lastData.facturas_pagar  || []).forEach(function (i) { items.push({ tipo: 'pagar',    referencia_id: i.factura_id }); });
        (lastData.facturas_declarar || []).forEach(function (i) { items.push({ tipo: 'declarar', referencia_id: i.factura_id }); });
        (lastData.repuestos_bajos || []).forEach(function (i) { items.push({ tipo: 'stock',    referencia_id: i.repuesto_id}); });

        if (items.length === 0) return;

        // Animación: colapsar todo el body
        const body = document.getElementById('notifBody');
        if (body) {
            body.style.transition = 'opacity .2s ease';
            body.style.opacity = '0';
            setTimeout(function () {
                body.innerHTML = emptyHTML();
                body.style.opacity = '1';
                updateBadgeFromDOM();
            }, 220);
        }

        postJson(URL_DESC_T, { items: items }).then(function () {
            lastData = null; // Forzar re-fetch en próximo poll
        });
    };

    /* ── Recalcular badge contando rows visibles ─────────────────────────── */
    function updateBadgeFromDOM() {
        const badge    = document.getElementById('notifBadge');
        const clearBtn = document.getElementById('notifClearBtn');
        const count    = document.querySelectorAll('.notif-row').length;
        if (badge) {
            badge.textContent   = count > 99 ? '99+' : String(count);
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
        if (clearBtn) clearBtn.style.display = count > 0 ? 'flex' : 'none';
    }

    function updateBadgeFromData(data) {
        const badge    = document.getElementById('notifBadge');
        const clearBtn = document.getElementById('notifClearBtn');
        const count    = data ? data.total : 0;
        if (badge) {
            badge.textContent   = count > 99 ? '99+' : String(count);
            badge.style.display = count > 0 ? 'flex' : 'none';
            // Pulso urgente si hay mora
            const hasMora = data && (data.cuotas_mora || []).length > 0;
            badge.classList.toggle('notif-badge-urgent', hasMora);
        }
        if (clearBtn) clearBtn.style.display = count > 0 ? 'flex' : 'none';
    }

    /* ── Verificar si quedó vacío tras descartes ─────────────────────────── */
    function recheckEmpty() {
        const body = document.getElementById('notifBody');
        if (!body) return;
        const hasRows = body.querySelectorAll('.notif-row').length > 0;
        const hasSections = body.querySelectorAll('.notif-section-header').length > 0;
        if (!hasRows && !hasSections) {
            body.innerHTML = emptyHTML();
        }
        // Remover secciones vacías (header sin rows)
        body.querySelectorAll('.notif-section-header').forEach(function (header) {
            let next = header.nextElementSibling;
            let hasItems = false;
            while (next && !next.classList.contains('notif-section-header')) {
                if (next.classList.contains('notif-row')) { hasItems = true; break; }
                next = next.nextElementSibling;
            }
            if (!hasItems) header.remove();
        });
    }

    function emptyHTML() {
        return '<div class="notif-empty">' +
            '<svg style="width:32px;height:32px;color:#22c55e;margin-bottom:6px;opacity:.8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>' +
            '</svg>' +
            '<span style="font-weight:600;color:var(--text)">Todo al día</span>' +
            '<span style="font-size:.72rem;color:var(--text-muted);margin-top:2px">No hay alertas pendientes</span>' +
            '</div>';
    }

    /* ── Dismiss button HTML ─────────────────────────────────────────────── */
    function dismissBtn(tipo, id) {
        return '<button class="notif-dismiss-btn" ' +
            'onclick="dismissNotifItem(\'' + tipo + '\',' + id + ',event)" ' +
            'title="Descartar">' +
            '<svg style="width:12px;height:12px;pointer-events:none" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>' +
            '</svg>' +
            '</button>';
    }

    /* ── Item builders ───────────────────────────────────────────────────── */
    function buildCuotaItem(item, accentColor, tipo) {
        const url = '/planes-cuotas/' + item.plan_id;
        const id  = 'nrow_' + tipo + '_' + item.cuota_id;
        return '<div class="notif-row" id="' + id + '">' +
            '<a class="notif-item" href="' + url + '">' +
            '<span class="notif-client">' + (item.cliente_nombre || '—') + '</span>' +
            '<span class="notif-meta">Venta #' + (item.numero_venta || '—') +
            ' &nbsp;·&nbsp; Cuota ' + item.numero_cuota + '/' + item.total_cuotas +
            ' &nbsp;·&nbsp; ' + fmtDate(item.fecha_vencimiento) + '</span>' +
            '<span class="notif-amount" style="color:' + accentColor + '">' + fmt(item.capital, item.interes, item.moneda) + '</span>' +
            '</a>' +
            dismissBtn(tipo, item.cuota_id) +
            '</div>';
    }

    function buildFacturaItem(item, accentColor, tipo) {
        const url = '/facturas/' + item.factura_id;
        const id  = 'nrow_' + tipo + '_' + item.factura_id;
        return '<div class="notif-row" id="' + id + '">' +
            '<a class="notif-item" href="' + url + '">' +
            '<span class="notif-client">' + (item.proveedor_nombre || '—') + '</span>' +
            '<span class="notif-meta">Factura N° ' + (item.numero_factura || '—') + ' &nbsp;·&nbsp; ' + fmtDate(item.fecha_factura) + '</span>' +
            '<span class="notif-amount" style="color:' + accentColor + '">' + fmtRaw(item.total_usd, item.moneda) + '</span>' +
            '</a>' +
            dismissBtn(tipo, item.factura_id) +
            '</div>';
    }

    function buildStockItem(item) {
        const url = '/repuestos/' + item.repuesto_id + '/edit';
        const id  = 'nrow_stock_' + item.repuesto_id;
        return '<div class="notif-row" id="' + id + '">' +
            '<a class="notif-item" href="' + url + '">' +
            '<span class="notif-client" style="color:#f59e0b">' + (item.codigo || '—') + '</span>' +
            '<span class="notif-meta">' + (item.descripcion || '—') + '</span>' +
            '<span class="notif-amount" style="color:var(--text-muted)">Stock: ' + item.stock_actual + ' / Mín: ' + item.stock_minimo + '</span>' +
            '</a>' +
            dismissBtn('stock', item.repuesto_id) +
            '</div>';
    }

    function sectionHeader(color, icon, label, count) {
        return '<div class="notif-section-header" style="color:' + color + '">' +
            '<span style="display:flex;align-items:center;gap:5px">' + icon + label + '</span>' +
            '<span style="font-size:.65rem;font-weight:700;opacity:.7">' + count + '</span>' +
            '</div>';
    }

    const ICONS = {
        stock:    '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>',
        mora:     '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        hoy:      '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>',
        prox:     '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        pagar:    '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
        declarar: '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>',
    };

    /* ── Render dropdown ─────────────────────────────────────────────────── */
    function renderDropdown(data) {
        let html = '';
        const mora     = data.cuotas_mora     || [];
        const hoy      = data.cuotas_hoy      || [];
        const prox     = data.cuotas_proximas || [];
        const pagar    = data.facturas_pagar  || [];
        const declarar = data.facturas_declarar || [];
        const stock    = data.repuestos_bajos || [];

        if (stock.length)    { html += sectionHeader('#f59e0b', ICONS.stock,    'Stock bajo mínimo',    stock.length);    stock.forEach(function(i){ html += buildStockItem(i); }); }
        if (mora.length)     { html += sectionHeader('#ef4444', ICONS.mora,     'En mora',              mora.length);     mora.forEach(function(i){  html += buildCuotaItem(i, '#ef4444', 'mora'); }); }
        if (hoy.length)      { html += sectionHeader('#f59e0b', ICONS.hoy,      'Cobros de hoy',        hoy.length);      hoy.forEach(function(i){   html += buildCuotaItem(i, '#f59e0b', 'hoy'); }); }
        if (prox.length)     { html += sectionHeader('#3b82f6', ICONS.prox,     'Próximas a vencer',    prox.length);     prox.forEach(function(i){  html += buildCuotaItem(i, '#3b82f6', 'prox'); }); }
        if (pagar.length)    { html += sectionHeader('#8b5cf6', ICONS.pagar,    'Facturas a pagar',     pagar.length);    pagar.forEach(function(i){ html += buildFacturaItem(i, '#8b5cf6', 'pagar'); }); }
        if (declarar.length) { html += sectionHeader('#10b981', ICONS.declarar, 'A declarar',           declarar.length); declarar.forEach(function(i){ html += buildFacturaItem(i, '#10b981', 'declarar'); }); }

        document.getElementById('notifBody').innerHTML = html || emptyHTML();
    }

    /* ── Fetch ───────────────────────────────────────────────────────────── */
    function fetchNotifications() {
        fetch(URL_FETCH, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;
            lastData = data;
            updateBadgeFromData(data);
            if (isOpen) renderDropdown(data);
        })
        .catch(function (err) { console.error('Notificaciones:', err); });
    }

    /* ── Toggle ──────────────────────────────────────────────────────────── */
    window.toggleNotifDropdown = function () {
        var dropdown = document.getElementById('notifDropdown');
        isOpen = !isOpen;
        if (isOpen) {
            erpShowDropdown(dropdown);
            if (lastData) {
                renderDropdown(lastData);
            } else {
                document.getElementById('notifBody').innerHTML =
                    '<div class="notif-empty"><span style="color:var(--text-muted);font-size:.8rem">Cargando...</span></div>';
                fetchNotifications();
            }
        } else {
            erpHideDropdown(dropdown);
        }
    };

    /* ── Close on outside click ──────────────────────────────────────────── */
    document.addEventListener('click', function (e) {
        var wrapper = document.getElementById('notifWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            erpHideDropdown(document.getElementById('notifDropdown'));
            isOpen = false;
        }
    });

    /* ── Init ────────────────────────────────────────────────────────────── */
    setTimeout(fetchNotifications, 800);
    setInterval(fetchNotifications, POLL_MS);
}());
</script>
@endcan
