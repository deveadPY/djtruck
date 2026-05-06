@extends('layouts.app')
@section('title', 'Configuración de Email')
@section('page-title', 'Comunicaciones & SMTP')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Centro de Mensajería</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">Configuración de servidor SMTP y plantillas transaccionales</p>
        </div>
        
        <div class="flex items-center gap-3">
             <div class="flex items-center gap-2 p-1 bg-surface2/40 backdrop-blur-md rounded-2xl border border-white/5 shadow-xl">
                <div class="px-4 py-2 flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ ($config?->activo ?? false) ? 'bg-primary animate-pulse' : 'bg-red-500' }}"></div>
                    <span class="text-[0.6rem] font-black uppercase tracking-widest text-white/70">
                        {{ ($config?->activo ?? false) ? 'Servicio Activo' : 'Servicio Inactivo' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-primary/10 border border-primary/20 text-primary text-xs font-black uppercase tracking-widest flex items-center gap-3 animate-fade-in">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-black uppercase tracking-widest space-y-2 animate-fade-in">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ session('error') ?? 'Se detectaron inconsistencias en la configuración' }}</span>
            </div>
            @if($errors->any())
                <ul class="pl-8 list-disc text-[0.65rem] opacity-80">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- ── Lado Izquierdo: Configuración SMTP ── --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 relative overflow-hidden group">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary via-accent to-transparent opacity-30"></div>
                
                <div class="erp-card-header !bg-transparent border-b border-white/5 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Credenciales del Servidor</h2>
                            <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest mt-1">Conexión SMTP Segura (TLS/SSL)</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-primary border border-white/5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" /><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('config.email.smtp.update') }}" class="erp-card-body p-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Driver --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Controlador de Correo</label>
                            <select name="mailer" class="form-input !bg-surface !h-12 !text-[0.65rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase">
                                <option value="smtp" {{ ($config?->mailer ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP (Protocolo Estándar)</option>
                                <option value="log" {{ ($config?->mailer ?? '') === 'log' ? 'selected' : '' }}>LOG (Solo Desarrollo)</option>
                                <option value="sendmail" {{ ($config?->mailer ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail (Servidor Local)</option>
                            </select>
                        </div>

                        {{-- Host --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Host SMTP</label>
                            <input type="text" name="host" value="{{ $config?->host ?? '' }}" placeholder="ej. smtp.gmail.com"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white">
                        </div>

                        {{-- Port --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Puerto de Enlace</label>
                            <input type="number" name="port" value="{{ $config?->port ?? 587 }}"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white">
                        </div>

                        {{-- Encryption --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Cifrado de Capa</label>
                            <select name="encryption" class="form-input !bg-surface !h-12 !text-[0.65rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white uppercase">
                                <option value="tls" {{ ($config?->encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (Seguridad Básica)</option>
                                <option value="ssl" {{ ($config?->encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL (Seguridad Alta)</option>
                                <option value="" {{ ($config?->encryption ?? '') === '' ? 'selected' : '' }}>Sin Cifrado (Vulnerable)</option>
                            </select>
                        </div>

                        {{-- Username --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Identidad de Autenticación</label>
                            <input type="text" name="username" value="{{ $config?->username ?? '' }}" placeholder="usuario@empresa.com"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white">
                        </div>

                        {{-- Password --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Llave de Seguridad (Password)</label>
                            <input type="password" name="password" 
                                placeholder="{{ $config?->password ? '•••••••••••• (Guardada)' : 'Ingresar nueva contraseña' }}"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white">
                        </div>

                        {{-- From Address --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Email Remitente (Alias)</label>
                            <input type="email" name="from_address" value="{{ $config?->from_address ?? '' }}" placeholder="noreply@empresa.com"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black font-mono rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white">
                        </div>

                        {{-- From Name --}}
                        <div class="space-y-2">
                            <label class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Nombre Descriptivo</label>
                            <input type="text" name="from_name" value="{{ $config?->from_name ?? '' }}" placeholder="DJ Trucks & Cars"
                                class="form-input !bg-surface !h-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white">
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-6 border-t border-white/5 pt-6">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative w-10 h-5">
                                <input type="hidden" name="activo" value="0">
                                <input type="checkbox" name="activo" value="1" {{ $config?->activo ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-full h-full bg-white/5 border border-white/10 rounded-full peer-checked:bg-primary transition-all"></div>
                                <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-all peer-checked:left-6"></div>
                            </div>
                            <span class="text-[0.65rem] font-black text-muted-foreground uppercase tracking-widest group-hover:text-white transition-colors">Ignorar entorno (.env) y usar esta Configuración</span>
                        </label>

                        <button type="submit" class="w-full md:w-auto h-12 px-8 bg-primary hover:bg-primary/90 text-[0.65rem] font-black uppercase tracking-[0.2em] text-white rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-95 flex items-center justify-center gap-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Sincronizar SMTP
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── Email de Prueba ── --}}
            <div class="erp-card !bg-surface/20 !backdrop-blur-md !border-white/5 overflow-hidden">
                <div class="p-6">
                    <form method="POST" action="{{ route('config.email.smtp.test') }}" class="flex flex-col md:flex-row items-end gap-6">
                        @csrf
                        <div class="flex-1 space-y-2 w-full">
                            <label class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-[0.2em] px-1">Verificación de Conectividad</label>
                            <div class="relative">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                                <input type="email" name="test_email" placeholder="destinatario@ejemplo.com" required
                                    class="form-input !bg-surface/50 !h-12 !pl-12 !text-[0.7rem] !font-black rounded-xl border-white/5 focus:ring-primary/20 transition-all text-white">
                            </div>
                        </div>
                        <button type="submit" class="w-full md:w-auto h-12 px-6 bg-white/5 hover:bg-white/10 border border-white/5 text-[0.6rem] font-black uppercase tracking-[0.2em] text-white rounded-xl transition-all flex items-center justify-center gap-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg> Inyectar Email Test
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Lado Derecho: Plantillas y Resumen ── --}}
        <div class="space-y-8">
            {{-- Quick Stats / Status --}}
            <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 p-6 space-y-4">
                <h3 class="text-[0.6rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic">Estado de Salud</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5">
                        <span class="text-[0.6rem] font-bold text-muted-foreground uppercase opacity-70">Envíos (Hoy)</span>
                        <span class="text-sm font-black text-white italic">{{ $logs->where('enviado_en', '>=', now()->startOfDay())->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-red-500/5 border border-red-500/10">
                        <span class="text-[0.6rem] font-bold text-red-500 uppercase opacity-70">Errores (Hoy)</span>
                        <span class="text-sm font-black text-red-500 italic">{{ $logs->where('estado', 'FALLIDO')->where('enviado_en', '>=', now()->startOfDay())->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Plantillas Lista Rápida --}}
            <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 overflow-hidden">
                <div class="erp-card-header !bg-transparent border-b border-white/5 p-5 flex items-center justify-between">
                    <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Plantillas</h2>
                    @can('configuracion.editar')
                        <a href="{{ route('config.email.plantilla.create') }}" class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary transition-transform hover:rotate-90">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </a>
                    @endcan
                </div>
                <div class="erp-card-body p-2 space-y-1">
                    @foreach($plantillas as $p)
                        <div class="group flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-all border border-transparent hover:border-white/5">
                            <div class="flex flex-col min-w-0 pr-4">
                                <span class="text-[0.65rem] font-black text-white uppercase italic truncate pr-2 tracking-tighter">{{ $p->nombre }}</span>
                                <span class="text-[0.5rem] font-bold text-muted-foreground uppercase opacity-40">{{ $p->tipo }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('config.email.plantilla.edit', $p->id) }}" 
                                    class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-muted-foreground hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── Historial de Envíos (Full Width Table) ── --}}
    <div class="mt-8 erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 overflow-hidden shadow-2xl">
        <div class="erp-card-header !bg-transparent border-b border-white/5 p-6 flex items-center justify-between">
            <div class="flex flex-col">
                <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">Registro de Tráfico Outbound</h2>
                <p class="text-[0.55rem] text-muted-foreground font-bold uppercase tracking-widest mt-1">Audit trail de los últimos 50 eventos</p>
            </div>
            <div class="w-2 h-2 rounded-full bg-accent animate-pulse shadow-[0_0_8px_#00d4aa]"></div>
        </div>

        <div class="overflow-x-auto hidden md:block">
            <table class="erp-table text-xs">
                <thead>
                    <tr class="text-[0.6rem] uppercase tracking-widest text-muted-foreground/60 border-b border-white/5">
                        <th class="!pl-6">Evento / Tipo</th>
                        <th>Destinatario</th>
                        <th>Asunto / Detalle</th>
                        <th>Estado</th>
                        <th>Origen</th>
                        <th class="!pr-6 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="hover:bg-white/5 group transition-colors">
                            <td class="!pl-6">
                                <span class="text-[0.6rem] font-black text-muted-foreground uppercase opacity-80 italic group-hover:text-primary transition-colors">{{ $log->tipo }}</span>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="text-[0.7rem] font-black text-white uppercase italic tracking-tighter">{{ $log->destinatario_nombre ?: 'Sin Nombre' }}</span>
                                    <span class="text-[0.6rem] text-muted-foreground font-mono opacity-50">{{ $log->destinatario_email }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col max-w-[300px]">
                                    <span class="text-[0.65rem] font-bold text-white/80 italic truncate">{{ $log->asunto ?: '—' }}</span>
                                    @if($log->error_mensaje)
                                        <span class="text-[0.55rem] text-red-500 font-bold uppercase truncate opacity-70 mt-1">{{ Str::limit($log->error_mensaje, 80) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($log->estado === 'ENVIADO')
                                    <span class="badge-status !bg-primary/10 !text-primary !border-primary/20 !text-[0.55rem] font-black italic">ENVIADO</span>
                                @elseif($log->estado === 'FALLIDO')
                                    <span class="badge-status !bg-red-500/10 !text-red-500 !border-red-500/20 !text-[0.55rem] font-black italic">FALLIDO</span>
                                @else
                                    <span class="badge-status !bg-white/5 !text-white/40 !border-white/10 !text-[0.55rem] font-black italic">SIMULADO</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-[0.6rem] font-black text-muted-foreground uppercase opacity-40 italic">
                                    {{ $log->enviado_por ? 'Usuario #' . $log->enviado_por : 'Auto-Sistema' }}
                                </span>
                            </td>
                            <td class="!pr-6 text-right">
                                <span class="text-[0.65rem] font-black text-white/40 italic font-mono uppercase">
                                    {{ \Carbon\Carbon::parse($log->enviado_en)->format('d.m.Y H:i') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-20 text-muted-foreground/20 font-black uppercase italic tracking-[0.5em] text-xs">Sin actividad detectada</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Logs Cards --}}
        <div class="md:hidden p-4 space-y-3">
            @forelse($logs as $log)
                <div class="p-4 rounded-xl bg-surface2/30 border border-white/5 space-y-3 shadow-xl">
                    <div class="flex items-center justify-between border-b border-white/5 pb-2">
                        <span class="text-[0.55rem] font-black text-primary italic uppercase tracking-widest">{{ $log->tipo }}</span>
                        @if($log->estado === 'ENVIADO')
                            <span class="text-[0.5rem] font-black text-primary uppercase italic tracking-widest">Enviado</span>
                        @elseif($log->estado === 'FALLIDO')
                            <span class="text-[0.5rem] font-black text-red-500 uppercase italic tracking-widest">Fallido</span>
                        @else
                            <span class="text-[0.5rem] font-black text-white/30 uppercase italic tracking-widest">Simulado</span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[0.7rem] font-black text-white uppercase italic tracking-tighter">{{ $log->destinatario_nombre ?: $log->destinatario_email }}</span>
                        <span class="text-[0.6rem] font-bold text-muted-foreground uppercase italic opacity-60 truncate">{{ $log->asunto ?: 'Sin Asunto' }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-white/5">
                        <span class="text-[0.5rem] font-black text-white/30 italic">{{ \Carbon\Carbon::parse($log->enviado_en)->format('d.m.Y H:i') }}</span>
                        @if($log->error_mensaje)
                            <span class="text-[0.5rem] font-black text-red-400 uppercase italic">Ver Error</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-10 opacity-20 font-black uppercase text-[0.6rem] tracking-widest italic tracking-[0.5em]">Sin Actividad Corriente</div>
            @endforelse
        </div>
    </div>
@endsection