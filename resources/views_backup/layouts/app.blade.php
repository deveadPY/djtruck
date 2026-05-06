<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ $empresa?->nombre_empresa ?? 'ERP Camiones & Repuestos' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #0f1117;
            --surface:      #1a1d27;
            --surface2:     #242736;
            --border:       #2d3148;
            --primary:      #6c63ff;
            --primary-hover:#5a52d5;
            --accent:       #00d4aa;
            --text:         #e2e8f0;
            --text-muted:   #94a3b8;
            --danger:       #ef4444;
            --success:      #22c55e;
            --warning:      #f59e0b;
            --sidebar-w:    240px;
            --sidebar-w-col: 62px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* ══ SIDEBAR ══════════════════════════════════════════════════════ */
        .sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            transition: width .25s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        .sidebar.collapsed { width: var(--sidebar-w-col); }

        /* ── Logo header ── */
        .sidebar-logo {
            padding: 1rem 1rem 1rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .65rem;
            min-height: 64px;
            flex-shrink: 0;
            position: relative;
        }
        .logo-img {
            width: 38px; height: 38px;
            border-radius: 10px;
            object-fit: contain;
            background: #fff; padding: 2px;
            flex-shrink: 0;
        }
        .logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .logo-text { overflow: hidden; white-space: nowrap; min-width: 0; }
        .logo-text .name { font-weight: 700; font-size: .88rem; line-height: 1.25; }
        .logo-text .sub  { font-size: .68rem; color: var(--text-muted); margin-top: 2px; }

        /* Botón colapsar */
        .sidebar-toggle {
            position: absolute;
            right: -13px; top: 50%;
            transform: translateY(-50%);
            width: 26px; height: 26px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 50%;
            cursor: pointer; z-index: 110;
            display: flex; align-items: center; justify-content: center;
            font-size: .72rem; color: var(--text-muted);
            transition: background .2s, color .2s;
            flex-shrink: 0;
        }
        .sidebar-toggle:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
        /* Rota la flecha cuando está colapsado */
        .sidebar.collapsed .sidebar-toggle { transform: translateY(-50%) rotate(180deg); }

        /* ── Nav (scrolleable) ── */
        .sidebar-nav {
            flex: 1;
            padding: .75rem 0;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        .nav-label {
            font-size: .62rem; font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .1em;
            padding: .65rem 1.1rem .2rem;
            white-space: nowrap; overflow: hidden;
            transition: all .2s;
        }
        /* Colapsado: convierte label en separador fino */
        .sidebar.collapsed .nav-label {
            font-size: 0; padding: .35rem .65rem;
            border-top: 1px solid var(--border);
            margin-top: .25rem;
        }

        .nav-link {
            display: flex; align-items: center; gap: .7rem;
            padding: .58rem 1.1rem;
            color: var(--text-muted);
            text-decoration: none; font-size: .855rem; font-weight: 500;
            border-radius: 8px;
            margin: .07rem .65rem;
            transition: background .15s, color .15s;
            white-space: nowrap; overflow: hidden;
            position: relative;
        }
        .nav-link:hover { background: var(--surface2); color: var(--text); }
        .nav-link.active {
            background: var(--surface2); color: var(--primary);
            border-left: 2px solid var(--primary);
            border-radius: 0 8px 8px 0;
            margin-left: .5rem;
        }
        .nav-link .icon { font-size: .95rem; width: 22px; text-align: center; flex-shrink: 0; }
        .nav-link .ltext { overflow: hidden; }

        /* Colapsado: solo icono centrado */
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: .58rem 0;
            margin: .07rem .65rem;
            border-left: none; border-radius: 8px;
        }
        .sidebar.collapsed .nav-link.active { margin-left: .65rem; border-left: none; }
        .sidebar.collapsed .nav-link .ltext { display: none; }

        /* Tooltip en modo colapsado */
        .sidebar.collapsed .nav-link::after {
            content: attr(data-tip);
            position: absolute;
            left: calc(var(--sidebar-w-col) + 4px);
            top: 50%; transform: translateY(-50%);
            background: var(--surface2); border: 1px solid var(--border);
            color: var(--text); padding: .28rem .7rem;
            border-radius: 6px; font-size: .78rem;
            white-space: nowrap; pointer-events: none;
            opacity: 0; transition: opacity .15s; z-index: 200;
        }
        .sidebar.collapsed .nav-link:hover::after { opacity: 1; }

        /* ── Footer usuario ── */
        .sidebar-footer {
            padding: .75rem; border-top: 1px solid var(--border); flex-shrink: 0;
        }
        .user-badge {
            display: flex; align-items: center; gap: .6rem;
            padding: .6rem .7rem;
            background: var(--surface2); border-radius: 10px; overflow: hidden;
        }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .82rem; flex-shrink: 0;
        }
        .user-info { overflow: hidden; white-space: nowrap; }
        .user-info .name { font-size: .78rem; font-weight: 600; }
        .user-info .role { font-size: .68rem; color: var(--text-muted); }
        .logout-btn {
            display: block; margin-top: .4rem; text-align: center;
            font-size: .72rem; color: var(--text-muted);
            text-decoration: none; padding: .35rem; border-radius: 6px; transition: .2s;
            white-space: nowrap;
        }
        .logout-btn:hover { background: var(--surface2); color: var(--danger); }
        .sidebar.collapsed .user-info,
        .sidebar.collapsed .logout-btn { display: none; }
        .sidebar.collapsed .user-badge { justify-content: center; padding: .5rem; }

        /* ══ MAIN ══════════════════════════════════════════════════════════ */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1; display: flex; flex-direction: column; min-height: 100vh;
            transition: margin-left .25s cubic-bezier(.4,0,.2,1);
        }
        .main.sidebar-collapsed { margin-left: var(--sidebar-w-col); }

        .topbar {
            padding: .9rem 1.75rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar h1 { font-size: 1.05rem; font-weight: 600; }
        .topbar .badge {
            background: var(--surface2); border: 1px solid var(--border);
            padding: .22rem .7rem; border-radius: 999px;
            font-size: .72rem; color: var(--text-muted);
        }
        .content { padding: 1.75rem 2rem; flex: 1; }

        /* ══ CARDS ════════════════════════════════════════════════════════ */
        .card { background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
        .card-header {
            padding:1.1rem 1.5rem; border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
        }
        .card-header h2 { font-size:.92rem; font-weight:600; }
        .card-body { padding:1.5rem; }

        /* ══ STATS ════════════════════════════════════════════════════════ */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:1.5rem; }
        .stat-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:1.4rem; position:relative; overflow:hidden; transition:transform .2s; }
        .stat-card:hover { transform:translateY(-2px); }
        .stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--accent); }
        .stat-card.danger::before  { background:var(--danger); }
        .stat-card.primary::before { background:var(--primary); }
        .stat-card.warning::before { background:var(--warning); }
        .stat-label { font-size:.73rem; color:var(--text-muted); font-weight:500; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.5rem; }
        .stat-value { font-size:1.7rem; font-weight:700; }
        .stat-icon  { position:absolute; right:1.4rem; top:1.4rem; font-size:1.7rem; opacity:.15; }

        /* ══ TABLES ═══════════════════════════════════════════════════════ */
        table { width:100%; border-collapse:collapse; font-size:.875rem; }
        th { text-align:left; padding:.7rem 1rem; font-size:.68rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid var(--border); }
        td { padding:.7rem 1rem; border-bottom:1px solid var(--border); }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:var(--surface2); }

        /* ══ BADGES ═══════════════════════════════════════════════════════ */
        .badge-status      { display:inline-block; padding:.18rem .6rem; border-radius:999px; font-size:.68rem; font-weight:600; }
        .badge-disponible  { background:rgba(34,197,94,.15);  color:var(--success); }
        .badge-preparacion { background:rgba(245,158,11,.15); color:var(--warning); }
        .badge-toma        { background:rgba(100,116,139,.15);color:var(--text-muted); }
        .badge-vendido     { background:rgba(239,68,68,.15);  color:var(--danger); }

        /* ══ BUTTONS ══════════════════════════════════════════════════════ */
        .btn { display:inline-flex; align-items:center; gap:.5rem; padding:.48rem 1rem; border-radius:8px; font-size:.8rem; font-weight:600; text-decoration:none; border:none; cursor:pointer; transition:.2s; }
        .btn-primary { background:var(--primary); color:#fff; }
        .btn-primary:hover { background:var(--primary-hover); }
        .btn-ghost { background:transparent; color:var(--text-muted); border:1px solid var(--border); }
        .btn-ghost:hover { background:var(--surface2); color:var(--text); }
    </style>
    @stack('styles')
</head>

<body>
@php
    // Garantizar $empresa siempre disponible en el layout (fallback directo a DB/cache)
    if (empty($empresa)) {
        try {
            $empresa = \App\Infrastructure\Settings\EmpresaSettings::get();
        } catch (\Throwable $e) {
            $empresa = null;
        }
    }
@endphp

{{-- ═══ SIDEBAR ══════════════════════════════════════════════════════════════ --}}
<aside class="sidebar" id="sidebar">

    {{-- Logo + nombre empresa --}}
    <div class="sidebar-logo">
        @if($empresa?->logo_path)
            <img src="{{ asset('storage/' . $empresa->logo_path) }}" alt="Logo" class="logo-img">
        @else
            <div class="logo-icon">🚛</div>
        @endif

        <div class="logo-text">
            <div class="name">{{ \Illuminate\Support\Str::limit($empresa?->nombre_empresa ?? 'ERP Camiones', 20) }}</div>
            <div class="sub">{{ $empresa?->ciudad ?: 'Sistema ERP' }}</div>
        </div>

        <button class="sidebar-toggle" id="sidebarToggle" title="Colapsar menú">‹</button>
    </div>

    {{-- Nav scrolleable --}}
    <nav class="sidebar-nav">

        <div class="nav-label">Principal</div>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           data-tip="Dashboard">
            <span class="icon">📊</span><span class="ltext">Dashboard</span>
        </a>

        @canany(['vehiculos.ver','repuestos.ver'])
        <div class="nav-label">Inventario</div>
        @can('vehiculos.ver')
        <a href="{{ route('vehicles.index') }}"
           class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}"
           data-tip="Vehículos">
            <span class="icon">🚛</span><span class="ltext">Vehículos</span>
        </a>
        @endcan
        @can('repuestos.ver')
        <a href="{{ route('repuestos.index') }}"
           class="nav-link {{ request()->routeIs('repuestos.*') ? 'active' : '' }}"
           data-tip="Repuestos">
            <span class="icon">🔧</span><span class="ltext">Repuestos</span>
        </a>
        @endcan
        @endcanany

        @canany(['clientes.ver','ventas.ver','cuotas.ver'])
        <div class="nav-label">Comercial</div>
        @can('clientes.ver')
        <a href="{{ route('clientes.index') }}"
           class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}"
           data-tip="Clientes">
            <span class="icon">👥</span><span class="ltext">Clientes</span>
        </a>
        @endcan
        @can('ventas.ver')
        <a href="{{ route('ventas.index') }}"
           class="nav-link {{ request()->routeIs('ventas.*') || request()->routeIs('planes_cuotas.*') ? 'active' : '' }}"
           data-tip="Ventas">
            <span class="icon">💰</span><span class="ltext">Ventas</span>
        </a>
        @endcan
        @endcanany

        @canany(['finanzas.ver','facturas.ver','proveedores.ver'])
        <div class="nav-label">Administración</div>
        @can('finanzas.ver')
        <a href="{{ route('finance.index') }}"
           class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}"
           data-tip="Finanzas / Cajas">
            <span class="icon">🏦</span><span class="ltext">Finanzas / Cajas</span>
        </a>
        @endcan
        @can('facturas.ver')
        <a href="{{ route('facturas.index') }}"
           class="nav-link {{ request()->routeIs('facturas.*') ? 'active' : '' }}"
           data-tip="Facturas y Gastos">
            <span class="icon">💸</span><span class="ltext">Facturas y Gastos</span>
        </a>
        @endcan
        @can('proveedores.ver')
        <a href="{{ route('proveedores.index') }}"
           class="nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}"
           data-tip="Proveedores">
            <span class="icon">🏷️</span><span class="ltext">Proveedores</span>
        </a>
        @endcan
        @endcanany

        <div class="nav-label">Reportes</div>
        <a href="{{ route('cotizaciones.index') }}"
           class="nav-link {{ request()->routeIs('cotizaciones.*') ? 'active' : '' }}"
           data-tip="Cotizaciones">
            <span class="icon">💱</span><span class="ltext">Cotizaciones</span>
        </a>

        @canany(['configuracion.ver','usuarios.ver','roles.ver'])
        <div class="nav-label">Sistema</div>
        @can('configuracion.ver')
        <a href="{{ route('config.index') }}"
           class="nav-link {{ request()->routeIs('config.*') ? 'active' : '' }}"
           data-tip="Configuración">
            <span class="icon">⚙️</span><span class="ltext">Configuración</span>
        </a>
        @endcan
        @can('usuarios.ver')
        <a href="{{ route('usuarios.index') }}"
           class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}"
           data-tip="Usuarios">
            <span class="icon">👤</span><span class="ltext">Usuarios</span>
        </a>
        @endcan
        @can('roles.ver')
        <a href="{{ route('roles.index') }}"
           class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
           data-tip="Roles y Permisos">
            <span class="icon">🔑</span><span class="ltext">Roles y Permisos</span>
        </a>
        @endcan
        @endcanany

    </nav>

    {{-- Footer: usuario --}}
    <div class="sidebar-footer">
        @auth
        <div class="user-badge">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="user-info">
                <div class="name">{{ auth()->user()->name }}</div>
                <div class="role">{{ ucfirst(auth()->user()->roles->first()?->name ?? auth()->user()->role ?? 'Usuario') }}</div>
            </div>
        </div>
        <a href="{{ route('logout') }}" class="logout-btn">⬅ Cerrar sesión</a>
        @endauth
    </div>

</aside>

{{-- ═══ MAIN ═══════════════════════════════════════════════════════════════════ --}}
<div class="main" id="mainContent">
    <div class="topbar">
        <h1>@yield('page-title', 'Panel')</h1>
        <span class="badge">{{ $empresa?->nombre_empresa ?? config('erp.app_name') }} — {{ now()->format('d/m/Y') }}</span>
    </div>
    <div class="content">
        @yield('content')
    </div>
</div>

@include('partials.confirm-modal')

<script>
/* ── Sidebar: colapso con persistencia en localStorage ──────── */
(function () {
    const sidebar = document.getElementById('sidebar');
    const main    = document.getElementById('mainContent');
    const btn     = document.getElementById('sidebarToggle');
    const KEY     = 'erp_sidebar_collapsed';

    function applyState(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);
        main.classList.toggle('sidebar-collapsed', collapsed);
    }

    // Restaurar estado guardado sin animación inicial
    sidebar.style.transition = 'none';
    main.style.transition    = 'none';
    applyState(localStorage.getItem(KEY) === 'true');
    // Re-activar transición después del primer render
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            sidebar.style.transition = '';
            main.style.transition    = '';
        });
    });

    btn.addEventListener('click', function () {
        const collapse = !sidebar.classList.contains('collapsed');
        applyState(collapse);
        localStorage.setItem(KEY, collapse ? 'true' : 'false');
    });
})();

/* ── Utilidades globales ─────────────────────────────────────── */
function formatNumber(n) {
    return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
@stack('scripts')
</body>
</html>
