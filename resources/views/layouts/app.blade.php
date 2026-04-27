<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ $empresa?->nombre_empresa ?? 'ERP Camiones & Repuestos' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: { DEFAULT: '#6c63ff', hover: '#5a52d5', light: '#8b83ff' },
                        accent: { DEFAULT: '#00d4aa', light: '#33e0be' },
                        surface: { DEFAULT: '#1a1d27', 2: '#242736', 3: '#2d3148' },
                        body: '#0f1117',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Inter', sans-serif; }
            /* Light mode defaults */
            :root {
                --bg:         #f4f6f9;
                --surface:    #ffffff;
                --surface2:   #f0f2f5;
                --surface3:   #e5e7eb;
                --border:     #e2e5ea;
                --text:       #1e293b;
                --text-muted: #64748b;
            }
            .dark {
                --bg:         #0f1117;
                --surface:    #1a1d27;
                --surface2:   #242736;
                --surface3:   #2d3148;
                --border:     #2d3148;
                --text:       #e2e8f0;
                --text-muted: #94a3b8;
            }
        }
        @layer components {
            /* ── Cards ─────────────────────────────────────── */
            .erp-card {
                @apply rounded-2xl border overflow-hidden transition-all duration-200;
                background: var(--surface);
                border-color: var(--border);
            }
            .erp-card-header {
                @apply px-4 py-3 sm:px-6 sm:py-4 flex items-center justify-between border-b;
                border-color: var(--border);
            }
            .erp-card-header h2 {
                @apply text-sm font-semibold;
                color: var(--text);
            }
            .erp-card-body {
                @apply p-4 sm:p-6;
            }

            /* ── Stats ─────────────────────────────────────── */
            .stat-card {
                @apply rounded-2xl border p-4 sm:p-5 relative overflow-hidden transition-transform duration-200 hover:-translate-y-0.5;
                background: var(--surface);
                border-color: var(--border);
            }
            .stat-card::before {
                content: '';
                @apply absolute top-0 left-0 right-0 h-[3px];
                background: #00d4aa;
            }
            .stat-card.danger::before  { background: #ef4444; }
            .stat-card.primary::before { background: #6c63ff; }
            .stat-card.warning::before { background: #f59e0b; }
            .stat-label {
                @apply text-[0.73rem] font-medium uppercase tracking-wider mb-2;
                color: var(--text-muted);
            }
            .stat-value {
                @apply text-[1.35rem] sm:text-[1.7rem] font-bold;
                color: var(--text);
            }
            .stat-icon {
                @apply absolute right-5 top-5 opacity-10;
                color: var(--text);
            }

            /* ── Tables ────────────────────────────────────── */
            .erp-table {
                @apply w-full text-sm;
                border-collapse: collapse;
            }
            .erp-table th {
                @apply text-left px-4 py-3 text-[0.68rem] font-semibold uppercase tracking-wider border-b;
                color: var(--text-muted);
                border-color: var(--border);
            }
            .erp-table td {
                @apply px-4 py-3 border-b;
                color: var(--text);
                border-color: var(--border);
            }
            .erp-table tr:last-child td { border-bottom: none; }
            .erp-table tbody tr:hover td { background: var(--surface2); }

            /* ── Badges ────────────────────────────────────── */
            .badge-status      { @apply inline-block px-2.5 py-0.5 rounded-full text-[0.68rem] font-semibold; }
            .badge-disponible  { @apply bg-green-500/15 text-green-500; }
            .badge-preparacion { @apply bg-amber-500/15 text-amber-500; }
            .badge-toma        { @apply bg-slate-500/15 text-slate-400; }
            .badge-vendido     { @apply bg-red-500/15 text-red-500; }

            /* ── Buttons ───────────────────────────────────── */
            .btn {
                @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[0.8rem] font-semibold no-underline border-none cursor-pointer transition-all duration-200;
            }
            .btn-primary {
                @apply bg-primary text-white hover:bg-primary-hover;
            }
            .btn-ghost {
                @apply bg-transparent border;
                color: var(--text-muted);
                border-color: var(--border);
            }
            .btn-ghost:hover {
                background: var(--surface2);
                color: var(--text);
            }
            .btn-danger {
                @apply bg-red-500/10 text-red-500 hover:bg-red-500/20;
            }
            .btn-success {
                @apply bg-green-500/10 text-green-500 hover:bg-green-500/20;
            }

            /* ── Form fields ───────────────────────────────── */
            .form-grid { @apply grid grid-cols-1 md:grid-cols-2 gap-5; }
            .form-group { @apply flex flex-col gap-1.5; }
            .form-group.full { @apply md:col-span-2; }
            .form-label {
                @apply text-[0.78rem] font-semibold uppercase tracking-wider;
                color: var(--text-muted);
            }
            .form-input {
                @apply w-full rounded-lg px-3.5 py-2.5 text-sm outline-none border transition-colors duration-200;
                background: var(--surface2);
                border-color: var(--border);
                color: var(--text);
                font-family: inherit;
            }
            .form-input:focus { @apply border-primary ring-1 ring-primary/30; }
            .form-input[type="file"] { @apply py-2 px-3 cursor-pointer; }
            select.form-input option { background: var(--surface2); color: var(--text); }

            /* ── Flash messages ────────────────────────────── */
            .flash-success {
                @apply bg-green-500/10 border border-green-500/30 text-green-500 px-4 py-3 rounded-xl text-sm mb-4;
            }
            .flash-error {
                @apply bg-red-500/10 border border-red-500/30 text-red-500 px-4 py-3 rounded-xl text-sm mb-4;
            }
            .flash-error ul { @apply ml-5 list-disc; }

            /* ── Sidebar nav link ──────────────────────────── */
            .nav-link {
                @apply flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2.5 my-0.5 text-[0.855rem] font-medium no-underline whitespace-nowrap overflow-hidden relative transition-all duration-150;
                color: var(--text-muted);
            }
            .nav-link:hover {
                background: var(--surface2);
                color: var(--text);
            }
            .nav-link.active {
                background: var(--surface2);
                @apply text-primary border-l-2 border-primary rounded-l-none ml-2;
            }
            .nav-link .icon-wrap {
                @apply w-5 h-5 flex-shrink-0;
            }
            .nav-link .icon-wrap svg {
                @apply w-5 h-5;
            }
            .nav-link .ltext { @apply overflow-hidden; }

            /* ── Notification bell styles ──────────────────── */
            .notif-row { display:flex; align-items:stretch; border-bottom:1px solid var(--border); }
            .notif-row:last-child { border-bottom:0; }
            .notif-row .notif-item { flex:1; min-width:0; border-bottom:0 !important; }
            .notif-dismiss-btn {
                flex-shrink:0; width:34px; display:flex; align-items:center; justify-content:center;
                border:none; background:transparent; cursor:pointer; color:var(--text-muted);
                border-radius:0 8px 8px 0; transition:color .15s, background .15s;
            }
            .notif-dismiss-btn:hover { color:#ef4444; background:rgba(239,68,68,.08); }
            .notif-item {
                @apply block px-4 py-3 border-b no-underline transition-colors duration-150;
                border-color: var(--border);
                color: var(--text);
            }
            .notif-item:hover { background: var(--surface2); }
            .notif-item:last-child { @apply border-b-0; }
            .notif-client { @apply block text-[0.82rem] font-semibold; color: var(--text); }
            .notif-meta { @apply block text-[0.72rem] mt-0.5; color: var(--text-muted); }
            .notif-amount { @apply block text-[0.72rem] font-semibold mt-0.5; }
            .notif-section-header { @apply px-4 py-2 text-[0.72rem] font-bold uppercase tracking-wider; }
            .notif-empty { @apply text-center py-6 text-[0.82rem]; color: var(--text-muted); }

            /* ── Mobile sidebar ───────────────────────────────────── */
            @media (max-width: 767px) {
                #sidebarToggle  { display: none !important; }
                #sidebar        { transform: translateX(-100%); }
                #sidebar.mobile-open { transform: translateX(0); }
                #mainContent    { margin-left: 0 !important; }
            }
        }
    </style>
    @stack('styles')
</head>

<body class="font-sans min-h-screen flex overflow-x-hidden" style="background: var(--bg); color: var(--text);">
    @php
        if (empty($empresa)) {
            try {
                $empresa = \App\Infrastructure\Settings\EmpresaSettings::get();
            } catch (\Throwable $e) {
                $empresa = null;
            }
        }
    @endphp

    {{-- ─── Mobile overlay backdrop ──────────────────────────────────────────── --}}
    <div id="sidebarOverlay" class="fixed inset-0 z-[90] bg-black/50 backdrop-blur-sm" style="display:none;"></div>

    {{-- ═══ SIDEBAR ══════════════════════════════════════════════════════════════ --}}
    <aside id="sidebar"
        class="fixed top-0 left-0 h-screen flex flex-col z-[100] overflow-hidden transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)] border-r"
        style="width: 250px; background: var(--surface); border-color: var(--border);">

        {{-- Logo + nombre empresa --}}
        <div class="px-4 py-4 flex items-center gap-3 min-h-[64px] flex-shrink-0 relative border-b"
            style="border-color: var(--border);">
            @if($empresa?->logo_path)
                <img src="{{ $empresa->logoUrl() }}" alt="Logo"
                    class="w-9 h-9 rounded-xl object-contain bg-white p-0.5 flex-shrink-0">
            @else
                <div
                    class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </div>
            @endif
            <div class="overflow-hidden whitespace-nowrap min-w-0 sidebar-text">
                <div class="font-bold text-[0.88rem] leading-tight" style="color: var(--text);">
                    {{ \Illuminate\Support\Str::limit($empresa?->nombre_empresa ?? 'ERP Camiones', 20) }}</div>
                <div class="text-[0.68rem] mt-0.5" style="color: var(--text-muted);">
                    {{ $empresa?->ciudad ?: 'Sistema ERP' }}</div>
            </div>
            <button id="sidebarToggle"
                class="absolute -right-3.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full border flex items-center justify-center text-[0.72rem] cursor-pointer z-[110] transition-all duration-200 hover:bg-primary hover:text-white hover:border-primary"
                style="background: var(--surface2); border-color: var(--border); color: var(--text-muted);"
                title="Colapsar menú">
                <svg class="w-3.5 h-3.5 transition-transform duration-300" id="sidebarArrow" fill="none"
                    viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        </div>

        {{-- Nav scrolleable --}}
        <nav class="flex-1 py-3 overflow-y-auto overflow-x-hidden sidebar-scroll">

            <div class="nav-section-label px-4 pb-1 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest whitespace-nowrap overflow-hidden"
                style="color: var(--text-muted);">Principal</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                data-tip="Dashboard">
                <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg></span>
                <span class="ltext">Dashboard</span>
            </a>

            @canany(['vehiculos.ver', 'repuestos.ver'])
                <div class="nav-section-label px-4 pb-1 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest whitespace-nowrap overflow-hidden"
                    style="color: var(--text-muted);">Inventario</div>
                @can('vehiculos.ver')
                    <a href="{{ route('vehicles.index') }}"
                        class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" data-tip="Vehículos">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                            </svg></span>
                        <span class="ltext">Vehículos</span>
                    </a>
                @endcan
                @can('repuestos.ver')
                    <a href="{{ route('repuestos.index') }}"
                        class="nav-link {{ request()->routeIs('repuestos.*') ? 'active' : '' }}" data-tip="Productos">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                            </svg></span>
                        <span class="ltext">Productos</span>
                    </a>
                @endcan
                @can('repuestos.crear')
                    <a href="{{ route('compras.index') }}"
                        class="nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}" data-tip="Compras">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg></span>
                        <span class="ltext">Compras</span>
                    </a>
                @endcan
            @endcanany

            @canany(['clientes.ver', 'ventas.ver', 'cuotas.ver'])
                <div class="nav-section-label px-4 pb-1 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest whitespace-nowrap overflow-hidden"
                    style="color: var(--text-muted);">Comercial</div>
                @can('clientes.ver')
                    <a href="{{ route('clientes.index') }}"
                        class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}" data-tip="Clientes">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg></span>
                        <span class="ltext">Clientes</span>
                    </a>
                @endcan
                @can('ventas.ver')
                    <a href="{{ route('ventas.index') }}"
                        class="nav-link {{ request()->routeIs('ventas.*') || request()->routeIs('planes_cuotas.*') ? 'active' : '' }}"
                        data-tip="Ventas">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg></span>
                        <span class="ltext">Ventas</span>
                    </a>
                @endcan
            @endcanany

            @canany(['finanzas.ver', 'facturas.ver', 'proveedores.ver'])
                <div class="nav-section-label px-4 pb-1 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest whitespace-nowrap overflow-hidden"
                    style="color: var(--text-muted);">Administración</div>
                @can('finanzas.ver')
                    <a href="{{ route('finance.index') }}"
                        class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}" data-tip="Finanzas / Cajas">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                            </svg></span>
                        <span class="ltext">Finanzas / Cajas</span>
                    </a>
                @endcan
                @can('facturas.ver')
                    <a href="{{ route('facturas.index') }}"
                        class="nav-link {{ request()->routeIs('facturas.*') ? 'active' : '' }}" data-tip="Facturas y Gastos">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg></span>
                        <span class="ltext">Facturas y Gastos</span>
                    </a>
                @endcan
                @can('proveedores.ver')
                    <a href="{{ route('proveedores.index') }}"
                        class="nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" data-tip="Proveedores">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                            </svg></span>
                        <span class="ltext">Proveedores</span>
                    </a>
                @endcan
            @endcanany

            <div class="nav-section-label px-4 pb-1 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest whitespace-nowrap overflow-hidden"
                style="color: var(--text-muted);">Reportes</div>
            @can('ventas.ver')
            <a href="{{ route('reportes.index') }}"
                class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" data-tip="Reportes & Análisis">
                <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg></span>
                <span class="ltext">Reportes</span>
            </a>
            @endcan

            @canany(['configuracion.ver', 'usuarios.ver', 'roles.ver'])
                <div class="nav-section-label px-4 pb-1 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest whitespace-nowrap overflow-hidden"
                    style="color: var(--text-muted);">Sistema</div>
                @can('configuracion.ver')
                    <a href="{{ route('config.index') }}"
                        class="nav-link {{ request()->routeIs('config.index') ? 'active' : '' }}"
                        data-tip="Configuración Empresa">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg></span>
                        <span class="ltext">Config. Empresa</span>
                    </a>
                    <a href="{{ route('config.email') }}"
                        class="nav-link {{ request()->routeIs('config.email*') ? 'active' : '' }}"
                        data-tip="Configuración de Email">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.909A2.25 2.25 0 0 1 2.25 8.993V6.75m19.5 0-9 5.625L2.25 6.75" />
                            </svg></span>
                        <span class="ltext">Email / SMTP</span>
                    </a>
                @endcan
                @can('usuarios.ver')
                    <a href="{{ route('usuarios.index') }}"
                        class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" data-tip="Usuarios">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg></span>
                        <span class="ltext">Usuarios</span>
                    </a>
                @endcan
                @can('roles.ver')
                    <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                        data-tip="Roles y Permisos">
                        <span class="icon-wrap"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg></span>
                        <span class="ltext">Roles y Permisos</span>
                    </a>
                @endcan
            @endcanany

        </nav>

        {{-- Footer: usuario --}}
        <div class="px-3 py-3 flex-shrink-0 border-t" style="border-color: var(--border);">
            @auth
                <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl overflow-hidden"
                    style="background: var(--surface2);">
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center font-bold text-[0.82rem] text-white flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden whitespace-nowrap sidebar-text">
                        <div class="text-[0.78rem] font-semibold" style="color: var(--text);">{{ auth()->user()->name }}
                        </div>
                        <div class="text-[0.68rem]" style="color: var(--text-muted);">
                            {{ ucfirst(auth()->user()->roles->first()?->name ?? auth()->user()->role ?? 'Usuario') }}</div>
                    </div>
                </div>
                <a href="{{ route('logout') }}"
                    class="block mt-1.5 text-center text-[0.72rem] no-underline py-1.5 rounded-lg transition-all duration-200 sidebar-text hover:bg-red-500/10 hover:text-red-500"
                    style="color: var(--text-muted);">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        Cerrar sesión
                    </span>
                </a>
            @endauth
        </div>

    </aside>

    {{-- ═══ MAIN ═══════════════════════════════════════════════════════════════════ --}}
    <div id="mainContent"
        class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]"
        style="margin-left: 250px;">

        {{-- Topbar --}}
        <header class="sticky top-0 z-50 flex items-center justify-between px-4 sm:px-7 py-3.5 border-b"
            style="background: var(--surface); border-color: var(--border);">
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

            <div class="flex items-center gap-3">
                {{-- Notificaciones bell --}}
                @can('cuotas.ver')
                    <div id="notifWrapper" class="relative">
                        <button onclick="toggleNotifDropdown()"
                            class="relative p-2 rounded-lg transition-colors duration-200 hover:opacity-80"
                            style="background: var(--surface2); color: var(--text-muted);">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <span id="notifBadge"
                                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[0.62rem] font-bold rounded-full flex items-center justify-center"
                                style="display:none;"></span>
                        </button>
                        <div id="notifDropdown"
                            class="absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-96 max-h-[400px] overflow-y-auto rounded-xl border shadow-xl z-[200]"
                            style="display:none; background: var(--surface); border-color: var(--border);">
                            <div class="px-4 py-3 border-b font-semibold text-sm flex items-center justify-between"
                                style="border-color: var(--border); color: var(--text);">
                                <span>Notificaciones</span>
                                <div class="flex items-center gap-3">
                                    <button id="notifClearBtn" onclick="clearAllNotifs(event)"
                                        style="display:none; font-size:.72rem; font-weight:500; color:var(--text-muted); background:none; border:none; cursor:pointer; padding:2px 6px; border-radius:5px; transition:color .15s,background .15s;"
                                        onmouseover="this.style.color='#ef4444';this.style.background='rgba(239,68,68,.08)'"
                                        onmouseout="this.style.color='var(--text-muted)';this.style.background='none'"
                                        title="Descartar todas las alertas de hoy">
                                        Limpiar todo
                                    </button>
                                    <a href="{{ route('notificaciones.index') }}"
                                        class="text-primary text-xs font-medium no-underline hover:underline">Ver todas →</a>
                                </div>
                            </div>
                            <div id="notifBody"></div>
                        </div>
                    </div>
                @endcan
                
                {{-- Cotizaciones --}}
                <div id="cotizWrapper" class="relative">
                    <button onclick="toggleCotizDropdown()" class="p-2 rounded-lg transition-colors duration-200 hover:opacity-80"
                        style="background: var(--surface2); color: var(--text-muted);" title="Cotizaciones">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </button>
                    <div id="cotizDropdown" class="absolute right-0 top-full mt-2 w-64 rounded-xl border shadow-xl z-[200] p-4"
                        style="display:none; background: var(--surface); border-color: var(--border);">
                        <h3 class="text-[0.65rem] font-bold uppercase tracking-wider mb-3 text-muted-foreground border-b pb-2" style="border-color: var(--border);">Cotización del día</h3>
                        <div id="cotizContent" class="space-y-3">
                            <div class="animate-pulse space-y-2">
                                <div class="h-4 bg-surface2 rounded w-3/4"></div>
                                <div class="h-4 bg-surface2 rounded w-1/2"></div>
                            </div>
                        </div>
                        <hr class="my-3" style="border-color: var(--border);">
                        <a href="{{ route('cotizaciones.index') }}" class="flex items-center justify-center gap-2 py-2 rounded-lg bg-primary/10 text-primary text-[0.7rem] font-bold hover:bg-primary/20 transition-colors no-underline">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                            </svg>
                            Historial / Cambiar
                        </a>
                    </div>
                </div>

                {{-- Dark / Light toggle --}}
                <button id="themeToggle" class="p-2 rounded-lg transition-colors duration-200 hover:opacity-80"
                    style="background: var(--surface2); color: var(--text-muted);" title="Cambiar tema">
                    {{-- Sol (light mode active → show moon) --}}
                    <svg id="iconMoon" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                    {{-- Luna (dark mode active → show sun) --}}
                    <svg id="iconSun" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                </button>

                {{-- Badge empresa --}}
                <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-[0.72rem] border"
                    style="background: var(--surface2); border-color: var(--border); color: var(--text-muted);">
                    {{ $empresa?->nombre_empresa ?? config('erp.app_name') }} — {{ now()->format('d/m/Y') }}
                </span>
            </div>
        </header>

        {{-- Content --}}
        <div class="flex-1 px-6 py-6 lg:px-8 lg:py-7">
            @yield('content')
        </div>
    </div>

    @include('partials.confirm-modal')

    <script>
            /* ── Theme toggle ──────────────────────────────────────────────── */
            (function () {
                const html = document.documentElement;
                const KEY = 'erp_theme';
                const moonIcon = document.getElementById('iconMoon');
                const sunIcon = document.getElementById('iconSun');
                const btn = document.getElementById('themeToggle');

                function applyTheme(dark) {
                    html.classList.toggle('dark', dark);
                    if (moonIcon && sunIcon) {
                        moonIcon.classList.toggle('hidden', dark);
                        sunIcon.classList.toggle('hidden', !dark);
                    }
                    // Update date input color scheme
                    document.querySelectorAll('input[type="date"]').forEach(function (el) {
                        el.style.colorScheme = dark ? 'dark' : 'light';
                    });
                }

                const saved = localStorage.getItem(KEY);
                applyTheme(saved ? saved === 'dark' : true); // default dark

                if (btn) {
                    btn.addEventListener('click', function () {
                        const nowDark = html.classList.contains('dark');
                        applyTheme(!nowDark);
                        localStorage.setItem(KEY, !nowDark ? 'dark' : 'light');
                    });
                }
            })();

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

        /* ── Tooltip for collapsed sidebar ─────────────────────────── */
        (function () {
            let tooltip = null;
            document.querySelectorAll('.nav-link[data-tip]').forEach(function (link) {
                link.addEventListener('mouseenter', function () {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar.style.width !== '68px') return;
                    tooltip = document.createElement('div');
                    tooltip.textContent = link.getAttribute('data-tip');
                    tooltip.className = 'fixed px-3 py-1.5 rounded-lg text-xs font-medium z-[300] shadow-lg whitespace-nowrap pointer-events-none';
                    tooltip.style.background = 'var(--surface2)';
                    tooltip.style.border = '1px solid var(--border)';
                    tooltip.style.color = 'var(--text)';
                    const rect = link.getBoundingClientRect();
                    tooltip.style.left = (rect.right + 8) + 'px';
                    tooltip.style.top = (rect.top + rect.height / 2) + 'px';
                    tooltip.style.transform = 'translateY(-50%)';
                    document.body.appendChild(tooltip);
                });
                link.addEventListener('mouseleave', function () {
                    if (tooltip) { tooltip.remove(); tooltip = null; }
                });
            });
        })();

        /* ── Utilidades globales ───────────────────────────────────── */
        function formatNumber(n) {
            return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    </script>
    @include('partials.notif-bell')
    @include('partials.header-cotizaciones')
    @stack('scripts')
</body>

</html>