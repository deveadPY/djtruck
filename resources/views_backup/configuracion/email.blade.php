@extends('layouts.app')
@section('title', 'Configuración de Email')
@section('page-title', '📧 Configuración de Correo')
@include('partials.form-styles')

@section('content')

@if(session('success'))
    <div class="alert alert-success">✔ {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">✖ {{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- SMTP CONFIGURATION                                                       --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-header">
        <h2>⚙ Configuración SMTP</h2>
        <span style="font-size:.75rem;color:var(--text-muted)">
            Credenciales del servidor de correo saliente. Si "Activo" está desactivado, se usa el .env
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('config.email.smtp.update') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="mailer">Tipo de Mailer</label>
                    <select name="mailer" id="mailer">
                        <option value="smtp"     {{ ($config?->mailer ?? 'smtp') === 'smtp'     ? 'selected' : '' }}>SMTP (recomendado)</option>
                        <option value="log"      {{ ($config?->mailer ?? '') === 'log'           ? 'selected' : '' }}>Log (desarrollo)</option>
                        <option value="sendmail" {{ ($config?->mailer ?? '') === 'sendmail'      ? 'selected' : '' }}>Sendmail</option>
                    </select>
                </div>

                <div class="form-group" id="smtpFields">
                    <label for="host">Host SMTP</label>
                    <input type="text" name="host" id="host"
                           value="{{ $config?->host ?? '' }}"
                           placeholder="smtp.gmail.com / mail.empresa.com">
                </div>

                <div class="form-group">
                    <label for="port">Puerto</label>
                    <input type="number" name="port" id="port"
                           value="{{ $config?->port ?? 587 }}"
                           min="1" max="65535">
                    <span class="form-hint">587 (TLS) · 465 (SSL) · 25 (sin cifrado)</span>
                </div>

                <div class="form-group">
                    <label for="encryption">Cifrado</label>
                    <select name="encryption" id="encryption">
                        <option value="tls"     {{ ($config?->encryption ?? 'tls') === 'tls'     ? 'selected' : '' }}>TLS (recomendado)</option>
                        <option value="ssl"     {{ ($config?->encryption ?? '') === 'ssl'         ? 'selected' : '' }}>SSL</option>
                        <option value="starttls"{{ ($config?->encryption ?? '') === 'starttls'    ? 'selected' : '' }}>STARTTLS</option>
                        <option value=""        {{ ($config?->encryption ?? '') === ''            ? 'selected' : '' }}>Sin cifrado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="username">Usuario / Email remitente</label>
                    <input type="text" name="username" id="username"
                           value="{{ $config?->username ?? '' }}"
                           placeholder="correo@empresa.com">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña SMTP</label>
                    <input type="password" name="password" id="password"
                           placeholder="{{ $config?->password ? '••••••••••• (dejar vacío para no cambiar)' : 'Ingresar contraseña' }}"
                           autocomplete="new-password">
                    @if($config?->password)
                        <span class="form-hint">La contraseña está guardada y encriptada. Dejar vacío para mantenerla.</span>
                    @endif
                </div>

                <div class="form-group">
                    <label for="from_address">Dirección "De:"</label>
                    <input type="email" name="from_address" id="from_address"
                           value="{{ $config?->from_address ?? '' }}"
                           placeholder="noreply@empresa.com">
                </div>

                <div class="form-group">
                    <label for="from_name">Nombre "De:"</label>
                    <input type="text" name="from_name" id="from_name"
                           value="{{ $config?->from_name ?? '' }}"
                           placeholder="DJ Trucks & Cars E.A.S">
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:1rem;margin-top:1rem;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.85rem">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1"
                           {{ $config?->activo ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--primary)">
                    <span>Activar esta configuración SMTP (sobrescribe el .env)</span>
                </label>
                <button type="submit" class="btn btn-primary">💾 Guardar configuración SMTP</button>
            </div>
        </form>

        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0">

        {{-- Test email ────────────────────────────────────────────────────── --}}
        <form method="POST" action="{{ route('config.email.smtp.test') }}"
              style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div class="form-group" style="flex:1;min-width:240px;margin-bottom:0">
                <label for="test_email">Enviar correo de prueba a:</label>
                <input type="email" name="test_email" id="test_email"
                       placeholder="destinatario@ejemplo.com" required>
            </div>
            <button type="submit" class="btn btn-ghost" style="color:var(--primary)">
                🧪 Enviar email de prueba
            </button>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- EMAIL TEMPLATES                                                           --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-header">
        <h2>📝 Plantillas de Email</h2>
        <span style="font-size:.75rem;color:var(--text-muted)">
            Edite el asunto y cuerpo HTML de cada tipo de correo. Use <code style="background:var(--surface2);padding:.1rem .3rem;border-radius:4px;">{{"{{"}}variable{{"}}"}}</code> para insertar datos dinámicos.
        </span>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Asunto</th>
                <th>Estado</th>
                <th>Última edición</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($plantillas as $p)
        <tr>
            <td style="font-weight:600">{{ $p->nombre }}</td>
            <td>
                @php
                    $tipoColors = [
                        'BIENVENIDA_VENTA'   => ['bg'=>'rgba(108,99,255,.15)', 'c'=>'#6c63ff'],
                        'RECIBO_CUOTA'       => ['bg'=>'rgba(34,197,94,.15)',  'c'=>'#22c55e'],
                        'CUOTA_VENCIDA'      => ['bg'=>'rgba(239,68,68,.15)',  'c'=>'#ef4444'],
                        'RECORDATORIO_CUOTA' => ['bg'=>'rgba(245,158,11,.15)', 'c'=>'#f59e0b'],
                        'ESTADO_CUENTA'      => ['bg'=>'rgba(108,99,255,.15)', 'c'=>'#6c63ff'],
                    ];
                    $tc = $tipoColors[$p->tipo] ?? ['bg'=>'rgba(148,163,184,.15)', 'c'=>'#94a3b8'];
                @endphp
                <span style="display:inline-block;padding:.18rem .6rem;border-radius:999px;font-size:.68rem;font-weight:700;background:{{ $tc['bg'] }};color:{{ $tc['c'] }}">
                    {{ $p->tipo }}
                </span>
            </td>
            <td style="font-size:.8rem;color:var(--text-muted);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                {{ $p->asunto }}
            </td>
            <td>
                @if($p->activo)
                    <span class="badge-status badge-disponible">Activa</span>
                @else
                    <span class="badge-status badge-toma">Inactiva</span>
                @endif
            </td>
            <td style="font-size:.75rem;color:var(--text-muted)">
                {{ $p->updated_at ? $p->updated_at->format('d/m/Y H:i') : $p->created_at->format('d/m/Y') }}
            </td>
            <td>
                @can('configuracion.editar')
                <a href="{{ route('config.email.plantilla.edit', $p->id) }}" class="btn btn-ghost" style="font-size:.75rem;padding:.3rem .7rem">
                    ✏️ Editar
                </a>
                @endcan
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- SEND LOG                                                                  --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header">
        <h2>📋 Historial de Envíos</h2>
        <span style="font-size:.75rem;color:var(--text-muted)">Últimos 50 emails enviados o con error</span>
    </div>
    @if($logs->isEmpty())
        <div style="padding:2rem;text-align:center;color:var(--text-muted)">No hay registros de envíos todavía.</div>
    @else
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Destinatario</th>
                <th>Asunto</th>
                <th>Estado</th>
                <th>Enviado por</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
        @foreach($logs as $log)
        <tr>
            <td>
                <span style="font-size:.7rem;font-weight:600;color:var(--text-muted)">{{ $log->tipo }}</span>
            </td>
            <td style="font-size:.8rem">
                <div style="font-weight:600">{{ $log->destinatario_nombre ?: '—' }}</div>
                <div style="color:var(--text-muted);font-size:.72rem">{{ $log->destinatario_email }}</div>
            </td>
            <td style="font-size:.78rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                {{ $log->asunto ?: '—' }}
                @if($log->error_mensaje)
                    <div style="color:var(--danger);font-size:.68rem;margin-top:.15rem">{{ Str::limit($log->error_mensaje, 80) }}</div>
                @endif
            </td>
            <td>
                @if($log->estado === 'ENVIADO')
                    <span class="badge-status badge-disponible">✔ Enviado</span>
                @elseif($log->estado === 'FALLIDO')
                    <span class="badge-status badge-vendido">✖ Fallido</span>
                @else
                    <span class="badge-status badge-toma">Simulado</span>
                @endif
            </td>
            <td style="font-size:.75rem;color:var(--text-muted)">
                {{ $log->enviado_por ? 'Usuario #'.$log->enviado_por : 'Sistema' }}
            </td>
            <td style="font-size:.75rem;color:var(--text-muted)">
                {{ \Carbon\Carbon::parse($log->enviado_en)->format('d/m/Y H:i') }}
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

@endsection
