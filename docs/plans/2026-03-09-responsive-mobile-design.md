# Responsive Mobile/Tablet — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make the ERP fully usable on smartphones (375px+) and tablets using a slide-out drawer navigation pattern, with zero PHP/backend changes.

**Architecture:** Pure Blade/CSS/JS changes. Mobile: sidebar hidden behind hamburger → slide-out drawer + dark overlay. Tablet (768–1023px): sidebar auto-collapses to 68px icons on load (no localStorage write). Desktop: unchanged. Component CSS upgraded to use responsive Tailwind variants. Inline `style=""` attributes in views replaced with Tailwind classes.

**Tech Stack:** Tailwind CSS (CDN, `<style type="text/tailwindcss">`), vanilla JS, Laravel Blade templates.

---

## Task 1: layouts/app.blade.php — Mobile drawer system

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

This is the biggest task. Break it into steps.

### Step 1: Add overlay div + hamburger button HTML

In `app.blade.php`, find:
```html
    {{-- ═══ SIDEBAR ══════════════════════════════════════════════════════════════ --}}
    <aside id="sidebar"
```

Replace that opening with:
```html
    {{-- ─── Mobile overlay backdrop ──────────────────────────────────────────── --}}
    <div id="sidebarOverlay" class="fixed inset-0 z-[90] bg-black/50 backdrop-blur-sm" style="display:none;"></div>

    {{-- ═══ SIDEBAR ══════════════════════════════════════════════════════════════ --}}
    <aside id="sidebar"
```

Then in the topbar `<header>`, find the opening line:
```html
        <header class="sticky top-0 z-50 flex items-center justify-between px-7 py-3.5 border-b"
```

Replace with:
```html
        <header class="sticky top-0 z-50 flex items-center justify-between px-4 sm:px-7 py-3.5 border-b"
```

Then find the `<h1>` in the header:
```html
            <h1 class="text-base font-semibold" style="color: var(--text);">@yield('page-title', 'Panel')</h1>
```

Replace with:
```html
            <div class="flex items-center gap-3">
                {{-- Hamburger — solo visible en mobile --}}
                <button id="mobileMenuBtn"
                    class="md:hidden p-2 rounded-lg transition-colors duration-200 hover:opacity-80"
                    style="background: var(--surface2); color: var(--text-muted);" aria-label="Abrir menú">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <h1 class="text-base font-semibold" style="color: var(--text);">@yield('page-title', 'Panel')</h1>
            </div>
```

### Step 2: Add CSS for mobile sidebar behavior

Inside the `<style type="text/tailwindcss">` block, find the LAST line before the closing `</style>` tag which is the `.notif-empty` rule:
```css
            .notif-empty { @apply text-center py-6 text-[0.82rem]; color: var(--text-muted); }
        }
    </style>
```

Replace with:
```css
            .notif-empty { @apply text-center py-6 text-[0.82rem]; color: var(--text-muted); }

            /* ── Cards responsive ─────────────────────────────────── */
            .erp-card-header { @apply px-4 py-3 sm:px-6 sm:py-4 flex items-center justify-between border-b; border-color: var(--border); }
            .erp-card-body   { @apply p-4 sm:p-6; }

            /* ── Stat cards responsive ────────────────────────────── */
            .stat-card  { @apply rounded-2xl border p-4 sm:p-5 relative overflow-hidden transition-transform duration-200 hover:-translate-y-0.5; background: var(--surface); border-color: var(--border); }
            .stat-value { @apply text-[1.35rem] sm:text-[1.7rem] font-bold; color: var(--text); }

            /* ── Mobile sidebar ───────────────────────────────────── */
            @media (max-width: 767px) {
                #sidebarToggle  { display: none !important; }
                #sidebar        { transform: translateX(-100%); }
                #sidebar.mobile-open { transform: translateX(0); }
                #mainContent    { margin-left: 0 !important; }
            }
        }
    </style>
```

**Note:** The existing `.erp-card-header` and `.stat-card`/`.stat-value` definitions must be REMOVED from where they are now (around lines 59, 72-96) to avoid duplicate class conflicts with Tailwind CSS. Find and delete the original definitions:

Remove these existing blocks from the CSS (they will be replaced by the new definitions above):
- The old `.erp-card-header` block (lines ~59-62)
- The old `.stat-card` block (lines ~72-76) — keep `::before` and variant rules
- The old `.stat-value` block (lines ~89-92)
- The old `.erp-card-body` block (lines ~67-69)

### Step 3: Fix notification dropdown width for mobile

Find:
```html
                        class="absolute right-0 top-full mt-2 w-96 max-h-[400px] overflow-y-auto rounded-xl border shadow-xl z-[200]"
```

Replace with:
```html
                        class="absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-96 max-h-[400px] overflow-y-auto rounded-xl border shadow-xl z-[200]"
```

### Step 4: Update the sidebar JavaScript IIFE

Find the sidebar IIFE that starts with:
```javascript
        /* ── Sidebar: colapso con persistencia ──────────────────────── */
        (function () {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('mainContent');
            const btn = document.getElementById('sidebarToggle');
```

Replace the **entire sidebar IIFE** (from `/* ── Sidebar: colapso` through the closing `})();` of that block) with this new version:

```javascript
        /* ── Sidebar: colapso + mobile drawer ───────────────────────── */
        (function () {
            const sidebar  = document.getElementById('sidebar');
            const main     = document.getElementById('mainContent');
            const btn      = document.getElementById('sidebarToggle');
            const arrow    = document.getElementById('sidebarArrow');
            const overlay  = document.getElementById('sidebarOverlay');
            const mobileBtn = document.getElementById('mobileMenuBtn');
            const KEY      = 'erp_sidebar_collapsed';
            const W_OPEN   = '250px';
            const W_CLOSED = '68px';

            /* ─ Desktop/Tablet collapse logic ─ */
            function applyState(collapsed) {
                sidebar.style.width      = collapsed ? W_CLOSED : W_OPEN;
                main.style.marginLeft    = collapsed ? W_CLOSED : W_OPEN;
                if (arrow) arrow.style.transform = collapsed ? 'rotate(180deg)' : '';
                sidebar.querySelectorAll('.sidebar-text, .ltext, .nav-section-label').forEach(function (el) {
                    el.style.display = collapsed ? 'none' : '';
                });
                sidebar.querySelectorAll('.nav-link').forEach(function (link) {
                    if (collapsed) {
                        link.style.justifyContent = 'center';
                        link.style.paddingLeft    = '0';
                        link.style.paddingRight   = '0';
                        link.style.borderLeft     = 'none';
                        link.style.borderRadius   = '8px';
                        link.style.marginLeft     = '10px';
                    } else {
                        link.style.justifyContent = '';
                        link.style.paddingLeft    = '';
                        link.style.paddingRight   = '';
                        link.style.borderLeft     = '';
                        link.style.borderRadius   = '';
                        link.style.marginLeft     = '';
                    }
                });
                if (collapsed) { sidebar.classList.add('sidebar-collapsed'); }
                else           { sidebar.classList.remove('sidebar-collapsed'); }
            }

            /* ─ Mobile drawer logic ─ */
            function openMobile() {
                sidebar.classList.add('mobile-open');
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            function closeMobile() {
                sidebar.classList.remove('mobile-open');
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }

            /* ─ Init without transition flash ─ */
            sidebar.style.transition = 'none';
            main.style.transition    = 'none';

            var isMobile = window.innerWidth < 768;
            var isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;

            if (!isMobile) {
                // Desktop: respect localStorage; Tablet: force collapsed without saving
                var savedCollapsed = isTablet ? true : (localStorage.getItem(KEY) === 'true');
                applyState(savedCollapsed);
            }

            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    sidebar.style.transition = '';
                    main.style.transition    = '';
                });
            });

            /* ─ Desktop toggle button ─ */
            if (btn) {
                btn.addEventListener('click', function () {
                    var isCollapsed = sidebar.style.width === W_CLOSED;
                    applyState(!isCollapsed);
                    localStorage.setItem(KEY, !isCollapsed ? 'true' : 'false');
                });
            }

            /* ─ Mobile hamburger button ─ */
            if (mobileBtn) {
                mobileBtn.addEventListener('click', openMobile);
            }

            /* ─ Overlay click → close ─ */
            if (overlay) {
                overlay.addEventListener('click', closeMobile);
            }

            /* ─ Swipe left to close on mobile ─ */
            var touchStartX = 0;
            sidebar.addEventListener('touchstart', function (e) { touchStartX = e.touches[0].clientX; }, { passive: true });
            sidebar.addEventListener('touchend', function (e) {
                var dx = touchStartX - e.changedTouches[0].clientX;
                if (dx > 50) closeMobile();
            }, { passive: true });

            /* ─ Resize: cleanup when crossing breakpoints ─ */
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768) {
                    closeMobile();
                    var nowTablet = window.innerWidth < 1024;
                    applyState(nowTablet ? true : (localStorage.getItem(KEY) === 'true'));
                }
            });
        })();
```

### Step 5: Manually verify

Open in browser at 375px width (Chrome DevTools → iPhone SE). Confirm:
- Content fills full width (no sidebar gap on left)
- Hamburger `☰` button appears in top-left of topbar
- Tapping hamburger slides sidebar in from left with dark overlay
- Tapping overlay closes the sidebar
- At 768px+ sidebar shows as collapsed icons
- At 1024px+ sidebar respects user's localStorage preference

---

## Task 2: planes_cuotas/show.blade.php — Fix inline grids and tables

**Files:**
- Modify: `resources/views/planes_cuotas/show.blade.php`

### Step 1: Fix the page header (inline flex → Tailwind)

Find (line 9–15):
```html
    <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem">
        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver a la venta</a>
        <h2 style="font-size:1rem;color:var(--text-muted)">Plan {{ $plan->tipo_plan }} — Venta
            #{{ $venta->numero_venta ?? $venta->id }}</h2>
        <span
            class="badge-status {{ $plan->estado === 'COMPLETADO' ? 'badge-disponible' : ($plan->estado === 'CANCELADO' ? 'badge-vendido' : 'badge-preparacion') }}">{{ $plan->estado }}</span>
    </div>
```

Replace with:
```html
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver a la venta</a>
        <h2 class="text-base" style="color:var(--text-muted)">Plan {{ $plan->tipo_plan }} — Venta #{{ $venta->numero_venta ?? $venta->id }}</h2>
        <span class="badge-status {{ $plan->estado === 'COMPLETADO' ? 'badge-disponible' : ($plan->estado === 'CANCELADO' ? 'badge-vendido' : 'badge-preparacion') }}">{{ $plan->estado }}</span>
    </div>
```

### Step 2: Fix the 4-column stats grid (inline → Tailwind)

Find (lines 18–34):
```html
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
        @php
            $stats = [
                'Cliente' => $cliente->razon_social ?? '—',
                'Capital Financiado' => '$ ' . number_format($plan->capital_total_usd, 2, ',', '.') . ' USD',
                'Cuotas Pagadas' => $pagado . ' de ' . $cuotas->count(),
                'Vencidas' => $vencidas,
            ];
        @endphp
        @foreach($stats as $l => $v)
            <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem">
                <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">{{ $l }}
                </div>
                <div style="font-weight:700;color:var(--accent)">{{ $v }}</div>
            </div>
        @endforeach
    </div>
```

Replace with:
```html
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @php
            $stats = [
                'Cliente'          => $cliente->razon_social ?? '—',
                'Capital Financiado' => '$ ' . number_format($plan->capital_total_usd, 2, ',', '.') . ' USD',
                'Cuotas Pagadas'   => $pagado . ' de ' . $cuotas->count(),
                'Vencidas'         => $vencidas,
            ];
        @endphp
        @foreach($stats as $l => $v)
            <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
                <div class="stat-label">{{ $l }}</div>
                <div class="font-bold text-accent text-base sm:text-lg">{{ $v }}</div>
            </div>
        @endforeach
    </div>
```

### Step 3: Wrap entregas table in overflow-x-auto

Find (inside the entregas section, line ~42–43):
```html
            <div class="erp-card-body" style="padding:0">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
```

Replace with:
```html
            <div class="erp-card-body" style="padding:0">
                <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
```

And close the new div after `</table>` in the entregas section:
```html
                    </tbody>
                </table>
                </div>{{-- overflow-x-auto --}}
            </div>
```

### Step 4: Wrap cuotas table in overflow-x-auto

Find (around line 72):
```html
        <div class="erp-card-body" style="padding:0">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Cuota</th>
```

Replace with:
```html
        <div class="erp-card-body" style="padding:0">
            <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Cuota</th>
```

And close after `</table>` in the cuotas section:
```html
                </tbody>
            </table>
            </div>{{-- overflow-x-auto --}}
        </div>
```

### Step 5: Verify in browser at 375px

Navigate to a plan detail. The 4 stat boxes should show 2×2 on mobile, 4 in a row on tablet+. Tables should scroll horizontally rather than breaking layout.

---

## Task 3: ventas/show.blade.php — Fix inline grids

**Files:**
- Modify: `resources/views/ventas/show.blade.php`

### Step 1: Fix page header

Find (line 9):
```html
    <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem">
```

Replace with:
```html
    <div class="flex flex-wrap items-center gap-3 mb-6">
```

### Step 2: Fix the 2-column card grid (Vehículo + Cliente)

Find (line 16):
```html
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem">
```

Replace with:
```html
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
```

### Step 3: Fix the financial summary 4-column grid

Find (line 49):
```html
        <div class="erp-card-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
```

Replace with:
```html
        <div class="erp-card-body">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
```

And close the new inner div after the `@endforeach`:
```html
            @endforeach
            </div>
        </div>
```

The items inside use `style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem"` — leave those inline styles as-is for this task.

### Step 4: Add overflow-x-auto to pagos table

Find (around line 71):
```html
            <div class="erp-card-body" style="padding:0">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Monto USD</th>
```

Replace with:
```html
            <div class="erp-card-body" style="padding:0">
                <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Monto USD</th>
```

And close after `</table>`:
```html
                </table>
                </div>{{-- overflow-x-auto --}}
```

### Step 5: Add overflow-x-auto to cuotas preview table

Find (around line 106):
```html
            <div class="erp-card-body" style="padding:0">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Cuota</th>
```

Replace with:
```html
            <div class="erp-card-body" style="padding:0">
                <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Cuota</th>
```

And close after `</table>`:
```html
                </table>
                </div>{{-- overflow-x-auto --}}
```

---

## Task 4: ventas/index.blade.php — Responsive action header

**Files:**
- Modify: `resources/views/ventas/index.blade.php`

### Step 1: Fix action header

Find (lines 9–16):
```html
    <div class="flex justify-end mb-4">
        <a href="{{ route('ventas.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nueva venta
        </a>
    </div>
```

Replace with:
```html
    <div class="flex flex-col sm:flex-row sm:justify-end mb-4">
        <a href="{{ route('ventas.create') }}" class="btn btn-primary w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nueva venta
        </a>
    </div>
```

---

## Task 5: clientes/index.blade.php — Responsive header

**Files:**
- Modify: `resources/views/clientes/index.blade.php`

### Step 1: Fix header layout

Find (lines 6–15):
```html
    <div class="flex justify-between items-center mb-4">
        <p class="text-sm" style="color: var(--text-muted);">Gestiona el historial corporativo, datos de facturación y línea
            de crédito.</p>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo Cliente
        </a>
    </div>
```

Replace with:
```html
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <p class="text-sm hidden sm:block" style="color: var(--text-muted);">Gestiona el historial corporativo, datos de facturación y línea de crédito.</p>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo Cliente
        </a>
    </div>
```

---

## Task 6: repuestos/index.blade.php — Fix inline styles + table scroll

**Files:**
- Modify: `resources/views/repuestos/index.blade.php`

### Step 1: Fix action header (inline style → Tailwind)

Find (lines 9–11):
```html
    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
        <a href="{{ route('repuestos.create') }}" class="btn btn-primary">+ Nuevo repuesto</a>
    </div>
```

Replace with:
```html
    <div class="flex flex-col sm:flex-row sm:justify-end mb-4">
        <a href="{{ route('repuestos.create') }}" class="btn btn-primary w-full sm:w-auto justify-center">+ Nuevo repuesto</a>
    </div>
```

### Step 2: Add overflow-x-auto wrapper around the table

Find (lines 17–18):
```html
        <div class="erp-card-body" style="padding:0">
            <table class="erp-table">
```

Replace with:
```html
        <div class="erp-card-body" style="padding:0">
            <div class="overflow-x-auto">
            <table class="erp-table">
```

Find the closing `</table>` in that block and add the closing div:
```html
            </table>
            </div>{{-- overflow-x-auto --}}
```

---

## Task 7: notificaciones/index.blade.php — Responsive stats grid

**Files:**
- Modify: `resources/views/notificaciones/index.blade.php`

The `.stat-row` and `.stat-mini` classes are not defined anywhere — they render unstyled. Convert to Tailwind.

### Step 1: Replace the entire stats row block

Find (lines 8–59):
```html
    <div class="stat-row">
        <div class="stat-mini">
            <div class="sm-label flex items-center gap-1.5" style="color:#ef4444">
```
... through ...
```html
        </div>
    </div>
```

Replace the entire `<div class="stat-row">...</div>` block with:
```html
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#ef4444">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                En Mora
            </div>
            <div class="text-2xl font-bold" style="color:#ef4444">{{ $cuotasMora->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#f59e0b">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Cobrar Hoy
            </div>
            <div class="text-2xl font-bold" style="color:#f59e0b">{{ $cuotasHoy->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#3b82f6">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Próx. 7 días
            </div>
            <div class="text-2xl font-bold" style="color:#3b82f6">{{ $cuotasProximas->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#8b5cf6">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Facturas Pagar
            </div>
            <div class="text-2xl font-bold" style="color:#8b5cf6">{{ $facturasPagar->total() }}</div>
        </div>
        <div class="rounded-xl border p-3 sm:p-4 col-span-2 sm:col-span-1" style="background:var(--surface2);border-color:var(--border)">
            <div class="flex items-center gap-1.5 text-[0.72rem] font-semibold uppercase tracking-wider mb-1.5" style="color:#10b981">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                </svg>
                A Declarar
            </div>
            <div class="text-2xl font-bold" style="color:#10b981">{{ $facturasDeclarar->total() }}</div>
        </div>
    </div>
```

### Step 2: Verify tabs are flex-wrap (already done in the view)

Check line 62 — it already has `flex-wrap: wrap` inline. No change needed.

---

## Task 8: Final cleanup and cross-browser check

### Step 1: Clear view cache

```bash
cd C:\xampp\htdocs\erp-camiones
php artisan view:clear
php artisan cache:clear
```

Expected output: `Views cleared!` and `Application cache cleared!`

### Step 2: Test checklist (Chrome DevTools)

Open each URL at **375px** (iPhone SE), **768px** (iPad portrait), **1024px** (iPad landscape), **1440px** (desktop):

| Page | 375px check | 768px check |
|------|-------------|-------------|
| Dashboard | Sidebar hidden, hamburger shows, 2-col stats grid | Sidebar 68px icons, 2-col stats |
| Ventas index | Full-width "Nueva venta" button | Button normal size |
| Clientes index | Full-width "Nuevo Cliente" button | Button normal size |
| Repuestos index | Full-width "Nuevo repuesto" button | Table scrolls |
| Ventas show | Cards stack 1-col, tables scroll | Cards 2-col |
| Planes cuotas show | Stats 2×2, tables scroll horizontally | Stats 4-col |
| Notificaciones | Stats 2-col grid, tabs wrap | Stats 3-col |

### Step 3: Test mobile drawer specifically

At 375px:
1. Tap `☰` → sidebar slides in, overlay appears
2. Tap a nav link → page navigates, sidebar closes
3. Tap overlay → sidebar closes, scroll restored
4. Swipe left on sidebar → sidebar closes

### Step 4: Commit

```bash
git add resources/views/layouts/app.blade.php \
        resources/views/planes_cuotas/show.blade.php \
        resources/views/ventas/show.blade.php \
        resources/views/ventas/index.blade.php \
        resources/views/clientes/index.blade.php \
        resources/views/repuestos/index.blade.php \
        resources/views/notificaciones/index.blade.php

git commit -m "feat: responsive mobile/tablet layout with slide-out drawer

- Add mobile hamburger + slide-out sidebar drawer with overlay
- Auto-collapse sidebar to 68px on tablet (768-1023px)
- Swipe-to-close gesture on mobile sidebar
- Fix notification dropdown overflow on mobile screens
- Responsive card/stat component CSS (px-4 sm:px-6, text sizes)
- Fix inline grid styles in planes_cuotas and ventas show views
- Add overflow-x-auto to all tables missing it
- Responsive action headers (flex-col sm:flex-row) in index pages
- Fix notificaciones stats row with proper Tailwind grid"
```
