<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — ERP Camiones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0f1117;
            --surface: #1a1d27;
            --surface2: #242736;
            --border: #2d3148;
            --primary: #6c63ff;
            --primary-hover: #5a52d5;
            --accent: #00d4aa;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --danger: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(108, 99, 255, .15) 0%, transparent 70%);
            top: -100px;
            right: -150px;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 212, 170, .1) 0%, transparent 70%);
            bottom: -50px;
            left: -100px;
            pointer-events: none;
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .4);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .logo h1 {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .logo p {
            font-size: .8rem;
            color: var(--text-muted);
            margin-top: .25rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: .5rem;
        }

        input[type=email],
        input[type=password] {
            width: 100%;
            padding: .8rem 1rem;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: inherit;
            font-size: .9rem;
            transition: border-color .2s;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: .9rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: inherit;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s;
            margin-top: .5rem;
        }

        .btn-submit:hover {
            opacity: .9;
        }

        .error-msg {
            background: rgba(239, 68, 68, .1);
            border: 1px solid rgba(239, 68, 68, .3);
            color: var(--danger);
            padding: .75rem 1rem;
            border-radius: 10px;
            font-size: .8rem;
            margin-bottom: 1rem;
        }

        .credentials-hint {
            margin-top: 1.5rem;
            background: var(--surface2);
            border-radius: 10px;
            padding: 1rem;
            font-size: .75rem;
            color: var(--text-muted);
        }

        .credentials-hint strong {
            color: var(--text);
            display: block;
            margin-bottom: .5rem;
        }

        .credentials-hint code {
            color: var(--accent);
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo">
            <div class="icon">🚛</div>
            <h1>ERP Camiones & Repuestos</h1>
            <p>Sistema de gestión empresarial</p>
        </div>

        @if($errors->any())
            <div class="error-msg">{{ $errors->first() }}</div>
        @endif
        @if(session('error'))
            <div class="error-msg">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@erp.com"
                    required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-submit">Iniciar sesión →</button>
        </form>

        <div class="credentials-hint">
            <strong>🔑 Credenciales de prueba:</strong>
            <code>admin@erp.com</code> / <code>admin123</code><br>
            <code>ventas@erp.com</code> / <code>ventas123</code>
        </div>
    </div>
</body>

</html>