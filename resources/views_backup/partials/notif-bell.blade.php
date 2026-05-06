@can('cuotas.ver')
<script>
(function () {
    'use strict';

    const POLL_MS  = 120000; // 2 minutes
    let lastData   = null;
    let isOpen     = false;

    /* ── Format money ──────────────────────────────────────────────────── */
    function fmt(capital, interes, moneda) {
        const total = parseFloat(capital || 0) + parseFloat(interes || 0);
        return (moneda || 'USD') + ' ' + total.toLocaleString('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    /* ── Format date (YYYY-MM-DD → DD/MM/YYYY) ─────────────────────────── */
    function fmtDate(d) {
        if (!d) return '';
        const [y, m, day] = d.split('-');
        return (day || '?') + '/' + (m || '?') + '/' + (y || '?');
    }

    /* ── Build one item HTML ────────────────────────────────────────────── */
    function buildItem(item, amountStyle) {
        const url = '/planes-cuotas/' + item.plan_id;
        return '<a class="notif-item" href="' + url + '">' +
            '<span class="notif-client">' + (item.cliente_nombre || '—') + '</span>' +
            '<span class="notif-meta">Venta #' + (item.numero_venta || '—') +
                ' · Cuota ' + item.numero_cuota + '/' + item.total_cuotas +
                ' · Vence ' + fmtDate(item.fecha_vencimiento) + '</span>' +
            '<span class="notif-amount" style="' + amountStyle + '">' +
                fmt(item.capital, item.interes, item.moneda) + '</span>' +
        '</a>';
    }

    /* ── Render dropdown body ───────────────────────────────────────────── */
    function renderDropdown(data) {
        let html = '';

        if (data.vencidas && data.vencidas.length > 0) {
            html += '<div class="notif-section-header" style="color:var(--danger)">⚠ Cuotas Vencidas (' + data.vencidas.length + ')</div>';
            data.vencidas.forEach(function (i) {
                html += buildItem(i, 'font-size:.72rem;font-weight:600;color:var(--danger)');
            });
        }

        if (data.en_mora && data.en_mora.length > 0) {
            html += '<div class="notif-section-header" style="color:#f97316">🔴 En Mora (' + data.en_mora.length + ')</div>';
            data.en_mora.forEach(function (i) {
                html += buildItem(i, 'font-size:.72rem;font-weight:600;color:#f97316');
            });
        }

        if (data.proximas && data.proximas.length > 0) {
            html += '<div class="notif-section-header" style="color:var(--warning)">📅 Próximas 7 días (' + data.proximas.length + ')</div>';
            data.proximas.forEach(function (i) {
                html += buildItem(i, 'font-size:.72rem;font-weight:600;color:var(--warning)');
            });
        }

        if (html === '') {
            html = '<div class="notif-empty">✔ No hay alertas pendientes de cuotas</div>';
        }

        document.getElementById('notifBody').innerHTML = html;
    }

    /* ── Update badge ───────────────────────────────────────────────────── */
    function updateBadge(total) {
        const badge = document.getElementById('notifBadge');
        if (!badge) return;
        if (total > 0) {
            badge.textContent = total > 99 ? '99+' : String(total);
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }

    /* ── Fetch from API ─────────────────────────────────────────────────── */
    function fetchNotifications() {
        fetch('{{ route("api.notificaciones") }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')
                                        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        : '',
            }
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function (data) {
            lastData = data;
            updateBadge(data.total);
            if (isOpen) renderDropdown(data);
        })
        .catch(function () { /* silent fail */ });
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
    fetchNotifications();
    setInterval(fetchNotifications, POLL_MS);
}());
</script>
@endcan
