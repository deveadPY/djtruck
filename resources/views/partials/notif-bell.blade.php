@can('cuotas.ver')
    <script>
        (function () {
            'use strict';

            const POLL_MS = 120000; // 2 minutes
            let lastData = null;
            let isOpen = false;

            /* ── localStorage dismiss (key rotates daily → auto-expira) ────────── */
            const TODAY_KEY = 'notif_dismissed_' + new Date().toISOString().slice(0, 10);

            function getDismissed() {
                try {
                    const raw = localStorage.getItem(TODAY_KEY);
                    return raw ? new Set(JSON.parse(raw)) : new Set();
                } catch (_) { return new Set(); }
            }

            function saveDismissed(set) {
                try {
                    localStorage.setItem(TODAY_KEY, JSON.stringify([...set]));
                    // Purgar claves de días anteriores
                    Object.keys(localStorage).forEach(function (k) {
                        if (k.startsWith('notif_dismissed_') && k !== TODAY_KEY) localStorage.removeItem(k);
                    });
                } catch (_) {}
            }

            /* ── Descartar un ítem individual ──────────────────────────────────── */
            window.dismissNotifItem = function (tipo, id, e) {
                e.preventDefault();
                e.stopPropagation();
                const dismissed = getDismissed();
                dismissed.add(tipo + '_' + id);
                saveDismissed(dismissed);
                if (lastData) { renderDropdown(lastData); updateBadgeFromData(lastData); }
            };

            /* ── Limpiar todas las alertas visibles ────────────────────────────── */
            window.clearAllNotifs = function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!lastData) return;
                const dismissed = getDismissed();
                (lastData.cuotas_mora     || []).forEach(function (i) { dismissed.add('mora_'     + i.cuota_id);   });
                (lastData.cuotas_hoy      || []).forEach(function (i) { dismissed.add('hoy_'      + i.cuota_id);   });
                (lastData.cuotas_proximas || []).forEach(function (i) { dismissed.add('prox_'     + i.cuota_id);   });
                (lastData.facturas_pagar  || []).forEach(function (i) { dismissed.add('pagar_'    + i.factura_id); });
                (lastData.facturas_declarar || []).forEach(function (i) { dismissed.add('declarar_' + i.factura_id); });
                saveDismissed(dismissed);
                renderDropdown(lastData);
                updateBadgeFromData(lastData);
            };

            /* ── Format money ──────────────────────────────────────────────────── */
            function fmt(capital, interes, moneda) {
                const total = parseFloat(capital || 0) + parseFloat(interes || 0);
                return (moneda || 'USD') + ' ' + total.toLocaleString('de-DE', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2,
                });
            }

            function fmtRaw(monto, moneda) {
                return (moneda || 'USD') + ' ' + parseFloat(monto || 0).toLocaleString('de-DE', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2,
                });
            }

            /* ── Format date (YYYY-MM-DD → DD/MM/YYYY) ─────────────────────────── */
            function fmtDate(d) {
                if (!d) return '';
                const [y, m, day] = d.split('-');
                return (day || '?') + '/' + (m || '?') + '/' + (y || '?');
            }

            /* ── Botón × de descarte ────────────────────────────────────────────── */
            function dismissBtn(tipo, id) {
                return '<button class="notif-dismiss-btn" ' +
                    'onclick="dismissNotifItem(\'' + tipo + '\',' + id + ',event)" ' +
                    'title="Descartar">' +
                    '<svg style="width:13px;height:13px;pointer-events:none" fill="none" viewBox="0 0 24 24" ' +
                    'stroke-width="2.5" stroke="currentColor">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>' +
                    '</svg>' +
                    '</button>';
            }

            /* ── Build one item HTML (Cuota) ────────────────────────────────────── */
            function buildCuotaItem(item, amountStyle, tipo) {
                const url = '/planes-cuotas/' + item.plan_id;
                return '<div class="notif-row">' +
                    '<a class="notif-item" href="' + url + '">' +
                    '<span class="notif-client">' + (item.cliente_nombre || '—') + '</span>' +
                    '<span class="notif-meta">Venta #' + (item.numero_venta || '—') +
                    ' · Cuota ' + item.numero_cuota + '/' + item.total_cuotas +
                    ' · Vence ' + fmtDate(item.fecha_vencimiento) + '</span>' +
                    '<span class="notif-amount" style="' + amountStyle + '">' +
                    fmt(item.capital, item.interes, item.moneda) + '</span>' +
                    '</a>' +
                    dismissBtn(tipo, item.cuota_id) +
                    '</div>';
            }

            /* ── Build one item HTML (Factura) ──────────────────────────────────── */
            function buildFacturaItem(item, amountStyle, tipo) {
                const url = '/facturas/' + item.factura_id;
                return '<div class="notif-row">' +
                    '<a class="notif-item" href="' + url + '">' +
                    '<span class="notif-client">' + (item.proveedor_nombre || '—') + '</span>' +
                    '<span class="notif-meta">Factura N° ' + (item.numero_factura || '—') +
                    ' · ' + fmtDate(item.fecha_factura) + '</span>' +
                    '<span class="notif-amount" style="' + amountStyle + '">' +
                    fmtRaw(item.total_usd, item.moneda) + '</span>' +
                    '</a>' +
                    dismissBtn(tipo, item.factura_id) +
                    '</div>';
            }

            /* ── Build one item HTML (Stock) ────────────────────────────────────── */
            function buildStockItem(item, tipo) {
                const url = '/repuestos/' + item.repuesto_id + '/edit';
                return '<div class="notif-row">' +
                    '<a class="notif-item" href="' + url + '">' +
                    '<span class="notif-client" style="color:#f59e0b">Stock Bajo: ' + (item.codigo || '—') + '</span>' +
                    '<span class="notif-meta">' + (item.descripcion || '—') + '</span>' +
                    '<span class="notif-amount" style="color:var(--text-muted)">' +
                    'Actual: ' + item.stock_actual + ' / Mín: ' + item.stock_minimo + '</span>' +
                    '</a>' +
                    dismissBtn(tipo, item.repuesto_id) +
                    '</div>';
            }

            /* ── Count active (non-dismissed) items ─────────────────────────────── */
            function countActive(data) {
                if (!data) return 0;
                const d = getDismissed();
                return (
                    (data.cuotas_mora     || []).filter(function (i) { return !d.has('mora_'     + i.cuota_id);   }).length +
                    (data.cuotas_hoy      || []).filter(function (i) { return !d.has('hoy_'      + i.cuota_id);   }).length +
                    (data.cuotas_proximas || []).filter(function (i) { return !d.has('prox_'     + i.cuota_id);   }).length +
                    (data.facturas_pagar  || []).filter(function (i) { return !d.has('pagar_'    + i.factura_id); }).length +
                    (data.facturas_declarar || []).filter(function (i) { return !d.has('declarar_' + i.factura_id); }).length +
                    (data.repuestos_bajos || []).filter(function (i) { return !d.has('stock_'   + i.repuesto_id); }).length
                );
            }

            /* ── Update badge (descontando descartados) ─────────────────────────── */
            function updateBadgeFromData(data) {
                const badge = document.getElementById('notifBadge');
                const clearBtn = document.getElementById('notifClearBtn');
                const count = countActive(data);
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : String(count);
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                if (clearBtn) clearBtn.style.display = count > 0 ? 'block' : 'none';
            }

            /* ── Render dropdown body ───────────────────────────────────────────── */
            function renderDropdown(data) {
                const d = getDismissed();
                let html = '';

                const mora      = (data.cuotas_mora     || []).filter(function (i) { return !d.has('mora_'     + i.cuota_id);   });
                const hoy       = (data.cuotas_hoy      || []).filter(function (i) { return !d.has('hoy_'      + i.cuota_id);   });
                const prox      = (data.cuotas_proximas || []).filter(function (i) { return !d.has('prox_'     + i.cuota_id);   });
                const pagar     = (data.facturas_pagar  || []).filter(function (i) { return !d.has('pagar_'    + i.factura_id); });
                const declarar  = (data.facturas_declarar || []).filter(function (i) { return !d.has('declarar_' + i.factura_id); });
                const stock     = (data.repuestos_bajos || []).filter(function (i) { return !d.has('stock_'   + i.repuesto_id); });

                if (stock.length > 0) {
                    html += '<div class="notif-section-header flex items-center gap-1.5" style="color:#f59e0b">' +
                        '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>' +
                        '</svg> Stock Bajo (' + stock.length + ')</div>';
                    stock.forEach(function (i) { html += buildStockItem(i, 'stock'); });
                }

                if (mora.length > 0) {
                    html += '<div class="notif-section-header flex items-center gap-1.5" style="color:#ef4444">' +
                        '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>' +
                        '</svg> En Mora (' + mora.length + ')</div>';
                    mora.forEach(function (i) { html += buildCuotaItem(i, 'font-size:.72rem;font-weight:600;color:#ef4444', 'mora'); });
                }

                if (hoy.length > 0) {
                    html += '<div class="notif-section-header flex items-center gap-1.5" style="color:#f59e0b">' +
                        '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>' +
                        '</svg> Cobros de Hoy (' + hoy.length + ')</div>';
                    hoy.forEach(function (i) { html += buildCuotaItem(i, 'font-size:.72rem;font-weight:600;color:#f59e0b', 'hoy'); });
                }

                if (prox.length > 0) {
                    html += '<div class="notif-section-header flex items-center gap-1.5" style="color:#3b82f6">' +
                        '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>' +
                        '</svg> Próximas a vencer (' + prox.length + ')</div>';
                    prox.forEach(function (i) { html += buildCuotaItem(i, 'font-size:.72rem;font-weight:600;color:#3b82f6', 'prox'); });
                }

                if (pagar.length > 0) {
                    html += '<div class="notif-section-header flex items-center gap-1.5" style="color:#8b5cf6">' +
                        '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>' +
                        '</svg> Facturas a Pagar (' + pagar.length + ')</div>';
                    pagar.forEach(function (i) { html += buildFacturaItem(i, 'font-size:.72rem;font-weight:600;color:#8b5cf6', 'pagar'); });
                }

                if (declarar.length > 0) {
                    html += '<div class="notif-section-header flex items-center gap-1.5" style="color:#10b981">' +
                        '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>' +
                        '</svg> A Declarar (' + declarar.length + ')</div>';
                    declarar.forEach(function (i) { html += buildFacturaItem(i, 'font-size:.72rem;font-weight:600;color:#10b981', 'declarar'); });
                }

                if (html === '') {
                    html = '<div class="notif-empty flex items-center justify-center gap-1.5">' +
                        '<svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>' +
                        '</svg> No hay alertas pendientes</div>';
                }

                document.getElementById('notifBody').innerHTML = html;
            }

            /* ── Fetch from API ─────────────────────────────────────────────────── */
            function fetchNotifications() {
                var token = document.querySelector('meta[name="csrf-token"]')
                    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                fetch('{{ route("notificaciones.feed") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    }
                })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data) return;
                        lastData = data;
                        updateBadgeFromData(data);
                        if (isOpen) renderDropdown(data);
                    })
                    .catch(function (e) { console.error('Notificaciones Error:', e); });
            }

            /* ── Toggle dropdown ────────────────────────────────────────────────── */
            window.toggleNotifDropdown = function () {
                var dropdown = document.getElementById('notifDropdown');
                isOpen = !isOpen;
                dropdown.style.display = isOpen ? 'block' : 'none';
                if (isOpen && lastData) {
                    renderDropdown(lastData);
                } else if (isOpen) {
                    document.getElementById('notifBody').innerHTML =
                        '<div class="notif-empty">Cargando...</div>';
                }
            };

            /* ── Close on outside click ─────────────────────────────────────────── */
            document.addEventListener('click', function (e) {
                var wrapper = document.getElementById('notifWrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    var dropdown = document.getElementById('notifDropdown');
                    if (dropdown) dropdown.style.display = 'none';
                    isOpen = false;
                }
            });

            /* ── Initial fetch + polling ────────────────────────────────────────── */
            setTimeout(fetchNotifications, 1000);
            setInterval(fetchNotifications, POLL_MS);
        }());
    </script>
@endcan
