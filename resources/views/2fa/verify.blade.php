@extends('layouts.auth')

@section('title', 'Verificar 2FA')

@section('content')
<div class="auth-container auth-container-narrow">
    <div class="auth-card">

        @if($errors->any())
            <div class="auth-alert auth-alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <h2 style="text-align:center;font-size:1.4rem;margin-bottom:.5rem;color:var(--text);">
            Verificación 2FA
        </h2>
        <p class="auth-muted" style="text-align:center;margin-bottom:2rem;">
            Ingresá el código de tu app autenticadora.<br>
            <small>O un recovery code si perdiste el dispositivo.</small>
        </p>

        <form method="POST" action="{{ route('2fa.verify.submit') }}">
            @csrf
            <input type="text"
                   name="code"
                   inputmode="text"
                   autocomplete="one-time-code"
                   autofocus
                   required
                   placeholder="000000  o  XXXX-XXXX-XXXX"
                   class="auth-input auth-input-otp-mix">

            <button type="submit" class="auth-btn auth-btn-primary">
                Verificar
            </button>
        </form>

        <p style="margin-top:1.5rem;text-align:center;">
            <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf
                <button type="submit" class="auth-link" style="background:none;border:0;cursor:pointer;font:inherit">Cerrar sesión</button>
            </form>
        </p>
    </div>
</div>
@endsection
