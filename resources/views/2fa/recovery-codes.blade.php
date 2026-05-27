@extends('layouts.auth')

@section('title', 'Recovery Codes 2FA')

@section('content')
<div class="auth-container">

    @if(session('success'))
        <div class="auth-alert auth-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="auth-alert auth-alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="auth-alert auth-alert-warn">
        <strong>⚠️ IMPORTANTE — Guardá estos códigos AHORA.</strong>
        <p style="margin:.5rem 0 0;font-size:.9rem;">
            Cada código sirve <strong>UNA sola vez</strong>. Son tu única forma de recuperar acceso
            si perdés el dispositivo con la app autenticadora. Imprimilos, guardalos en un gestor
            de contraseñas o en un lugar seguro.
        </p>
    </div>

    <div class="auth-card">
        @if(count($recovery_codes) > 0)
            <div class="auth-recovery-grid">
                @foreach($recovery_codes as $code)
                    <div class="auth-recovery-code">{{ $code }}</div>
                @endforeach
            </div>

            <div class="auth-actions-row no-print">
                <button onclick="window.print()" class="auth-btn auth-btn-secondary">
                    🖨️ Imprimir
                </button>
                <button id="copyCodesBtn" class="auth-btn auth-btn-secondary"
                        data-codes="{{ implode("\n", $recovery_codes) }}">
                    📋 Copiar todos
                </button>
                <a href="{{ route('dashboard') }}" class="auth-btn auth-btn-success">
                    ✓ Ya los guardé, ir al panel
                </a>
            </div>
        @else
            <p class="auth-muted">No quedan recovery codes disponibles.</p>
        @endif
    </div>

    <div class="auth-card no-print" style="margin-top:1.5rem;">
        <h4 class="auth-title">Regenerar códigos</h4>
        <p class="auth-muted" style="margin-bottom:1rem;">
            Si los códigos fueron expuestos, podés regenerar un nuevo set. Los anteriores quedarán invalidados.
        </p>

        <form method="POST" action="{{ route('2fa.regenerate') }}"
              onsubmit="return confirm('¿Confirmás? Los códigos actuales dejarán de funcionar.');">
            @csrf
            <input type="password"
                   name="password"
                   required
                   placeholder="Contraseña actual"
                   class="auth-input"
                   style="margin-bottom:.75rem;">
            <button type="submit" class="auth-btn auth-btn-warn">
                Regenerar 8 nuevos códigos
            </button>
        </form>
    </div>
</div>

<script>
    (function () {
        const btn = document.getElementById('copyCodesBtn');
        btn?.addEventListener('click', () => {
            const codes = btn.getAttribute('data-codes') || '';
            navigator.clipboard.writeText(codes).then(() => {
                btn.textContent = '✓ Copiado';
                setTimeout(() => btn.textContent = '📋 Copiar todos', 2500);
            });
        });
    })();
</script>
@endsection
