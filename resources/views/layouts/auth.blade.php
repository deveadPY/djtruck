<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Aplicar tema desde localStorage ANTES del primer render
        (function() {
            const saved = localStorage.getItem('erp_theme');
            const dark = saved ? saved === 'dark' : true;
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>
    <title>@yield('title') — {{ $empresa?->nombre_empresa ?? 'ERP Camiones & Repuestos' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        /* === Theme variables (light/dark) === */
        :root {
            --bg:             #f8fafc;
            --surface:        #ffffff;
            --surface2:       #f1f5f9;
            --surface3:       #e2e8f0;
            --border:         #cbd5e1;
            --text:           #0f172a;
            --text-muted:     #475569;
            --primary:        #6c63ff;
            --primary-hover:  #5a52d5;
            --accent:         #059669;
            --warn:           #f59e0b;
            --warn-bg:        #fff8e1;
            --warn-border:    #f59e0b;
            --danger:         #dc2626;
            --danger-bg:      #fef2f2;
            --success-bg:     #dcfce7;
            --success-border: #16a34a;
            --shadow:         0 1px 3px 0 rgba(0,0,0,.1), 0 1px 2px -1px rgba(0,0,0,.1);
            --shadow-lg:      0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
        }
        html.dark {
            --bg:             #121212;
            --surface:        #1e1e1e;
            --surface2:       #2c2c2c;
            --surface3:       #383838;
            --border:         #2d2d2d;
            --text:           #e2e8f0;
            --text-muted:     #94a3b8;
            --primary:        #bb86fc;
            --primary-hover:  #a370f7;
            --accent:         #03dac6;
            --warn:           #fbbf24;
            --warn-bg:        rgba(251, 191, 36, .12);
            --warn-border:    #fbbf24;
            --danger:         #f87171;
            --danger-bg:      rgba(248, 113, 113, .12);
            --success-bg:     rgba(34, 197, 94, .15);
            --success-border: #4ade80;
            --shadow:         0 4px 6px -1px rgba(0,0,0,.3), 0 2px 4px -2px rgba(0,0,0,.3);
            --shadow-lg:      0 20px 25px -5px rgba(0,0,0,.4), 0 8px 10px -6px rgba(0,0,0,.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color .3s ease, color .3s ease;
        }

        /* === Top bar === */
        .auth-topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: .75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }
        .auth-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-weight: 700;
            color: var(--text);
            text-decoration: none;
        }
        .auth-brand-logo {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: grid; place-items: center;
            color: #fff; font-weight: 800;
        }
        .auth-topbar-actions {
            display: flex; gap: .5rem; align-items: center;
        }
        .auth-icon-btn {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            width: 36px; height: 36px;
            border-radius: 8px;
            display: grid; place-items: center;
            cursor: pointer;
            transition: all .2s;
            font-size: 1.05rem;
        }
        .auth-icon-btn:hover { background: var(--surface3); }
        .auth-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: .9rem;
            padding: .5rem .75rem;
            border-radius: 6px;
        }
        .auth-link:hover { color: var(--text); background: var(--surface2); }

        /* === Main === */
        .auth-main {
            flex: 1;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .auth-container { width: 100%; max-width: 760px; }
        .auth-container-narrow { max-width: 440px; margin: 4rem auto 0; }

        /* === Cards === */
        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }
        .auth-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        @media (max-width: 720px) {
            .auth-card-grid { grid-template-columns: 1fr; }
        }

        .auth-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: .75rem;
            color: var(--text);
        }
        .auth-text { color: var(--text); }
        .auth-muted { color: var(--text-muted); font-size: .9rem; }

        /* === Alerts === */
        .auth-alert {
            padding: 1rem 1.15rem;
            border-radius: 10px;
            border-left: 4px solid;
            margin-bottom: 1.5rem;
            font-size: .95rem;
        }
        .auth-alert-warn    { background: var(--warn-bg); border-color: var(--warn-border); color: var(--text); }
        .auth-alert-error   { background: var(--danger-bg); border-color: var(--danger); color: var(--text); }
        .auth-alert-success { background: var(--success-bg); border-color: var(--success-border); color: var(--text); }

        /* === QR === */
        .auth-qr-box {
            background: #ffffff;
            padding: 1rem;
            display: inline-block;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        html.dark .auth-qr-box {
            background: #ffffff;       /* QR siempre sobre blanco para máximo contraste de cámara */
            border-color: var(--border);
        }
        .auth-qr-box svg { display: block; }

        /* === Secret code (manual) === */
        .auth-code-inline {
            background: var(--surface2);
            color: var(--text);
            padding: .65rem .85rem;
            border-radius: 6px;
            display: inline-block;
            margin-top: .5rem;
            font-family: 'Courier New', monospace;
            font-size: .95rem;
            letter-spacing: 1px;
            word-break: break-all;
            border: 1px solid var(--border);
        }

        /* === Inputs === */
        .auth-input {
            width: 100%;
            padding: .85rem;
            font-size: 1rem;
            background: var(--surface2);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            transition: border-color .2s, background-color .2s;
        }
        .auth-input:focus {
            border-color: var(--primary);
            background: var(--surface);
        }
        .auth-input-otp {
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: .5rem;
            font-weight: 600;
        }
        .auth-input-otp-mix {
            font-size: 1.15rem;
            text-align: center;
            letter-spacing: .15rem;
            text-transform: uppercase;
        }

        /* === Buttons === */
        .auth-btn {
            display: inline-block;
            padding: .85rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .95rem;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
            text-align: center;
        }
        .auth-btn-primary { background: var(--primary); color: #fff; width: 100%; margin-top: 1rem; }
        .auth-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .auth-btn-secondary { background: var(--surface3); color: var(--text); }
        .auth-btn-secondary:hover { background: var(--border); }
        .auth-btn-warn    { background: var(--warn); color: #1f2937; }
        .auth-btn-warn:hover    { filter: brightness(1.1); }
        .auth-btn-success { background: var(--accent); color: #fff; }
        .auth-btn-success:hover { filter: brightness(1.1); }

        /* === Recovery code grid === */
        .auth-recovery-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-top: 1rem;
        }
        @media (max-width: 480px) {
            .auth-recovery-grid { grid-template-columns: 1fr; }
        }
        .auth-recovery-code {
            background: var(--surface2);
            color: var(--text);
            padding: .85rem 1rem;
            border-radius: 8px;
            letter-spacing: .05rem;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: 500;
            border: 1px solid var(--border);
        }

        .auth-actions-row {
            margin-top: 1.5rem;
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }

        /* === Print: solo recovery codes === */
        @media print {
            .auth-topbar, .no-print { display: none !important; }
            .auth-card { box-shadow: none; border: 1px solid #000; }
            body { background: #fff !important; color: #000 !important; }
            .auth-recovery-code { background: #f5f5f5 !important; color: #000 !important; border: 1px dashed #888 !important; }
        }
    </style>
</head>

<body>
    <header class="auth-topbar">
        <a href="{{ route('dashboard') }}" class="auth-brand">
            <span class="auth-brand-logo">
                @if($empresa?->logo_path && file_exists(public_path($empresa->logo_path)))
                    <img src="{{ asset($empresa->logo_path) }}" alt="logo" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                @else
                    {{ strtoupper(substr($empresa?->nombre_empresa ?? 'DT', 0, 2)) }}
                @endif
            </span>
            <span>{{ $empresa?->nombre_empresa ?? 'DJ Trucks' }}</span>
        </a>

        <div class="auth-topbar-actions no-print">
            <button type="button" class="auth-icon-btn" id="themeToggle" title="Cambiar tema">
                <span id="themeIcon">☀️</span>
            </button>
            @auth
                <a href="{{ route('logout') }}" class="auth-link">Cerrar sesión</a>
            @endauth
        </div>
    </header>

    <main class="auth-main">
        @yield('content')
    </main>

    <script>
        (function () {
            const toggle = document.getElementById('themeToggle');
            const icon   = document.getElementById('themeIcon');
            const update = () => { icon.textContent = document.documentElement.classList.contains('dark') ? '☀️' : '🌙'; };
            update();
            toggle?.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('erp_theme', isDark ? 'dark' : 'light');
                update();
            });
        })();
    </script>
</body>
</html>
