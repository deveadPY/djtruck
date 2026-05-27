@extends('layouts.auth')

@section('title', 'Configurar 2FA')

@section('content')
<div class="auth-container">

    @if($errors->any())
        <div class="auth-alert auth-alert-error">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="auth-alert auth-alert-warn">
        <strong>⚠️ Su rol requiere 2FA.</strong><br>
        Para continuar usando el sistema, debe activar la autenticación de dos factores.
    </div>

    <div class="auth-card">
        <div class="auth-card-grid">

            <div>
                <h3 class="auth-title">Paso 1 — Escanear QR</h3>
                <p class="auth-muted" style="margin-bottom:1rem;">
                    Usá <strong>Google Authenticator</strong> (Play Store / App Store) u otra app autenticadora
                    (Microsoft Authenticator, Authy, 1Password) para escanear:
                </p>

                <div class="auth-qr-box">
                    {!! $qr_svg !!}
                </div>

                <p class="auth-muted" style="margin-top:1rem;font-size:.85rem;">
                    ¿No podés escanear? Ingresá manualmente este código en tu app:
                </p>
                <code class="auth-code-inline">{{ $secret }}</code>
            </div>

            <div>
                <h3 class="auth-title">Paso 2 — Confirmar</h3>
                <p class="auth-muted" style="margin-bottom:1rem;">
                    Ingresá el código de 6 dígitos que muestra tu app autenticadora:
                </p>

                <form method="POST" action="{{ route('2fa.confirm') }}">
                    @csrf
                    <input type="text"
                           name="code"
                           inputmode="numeric"
                           pattern="\d{6}"
                           maxlength="6"
                           autocomplete="one-time-code"
                           autofocus
                           required
                           placeholder="000000"
                           class="auth-input auth-input-otp">

                    <button type="submit" class="auth-btn auth-btn-primary">
                        Verificar y activar 2FA
                    </button>
                </form>

                <p class="auth-muted" style="margin-top:1.5rem;font-size:.85rem;">
                    <strong style="color:var(--text);">Recovery codes:</strong> tras confirmar, recibirás
                    8 códigos de un solo uso. Guardalos en un lugar seguro — son tu única forma de
                    acceder si perdés el dispositivo.
                </p>
            </div>

        </div>
    </div>
</div>
@endsection
