@extends('layouts.app')

@section('title', 'SIFEN — Gestión Avanzada')
@section('page-title', 'Comprobantes Electrónicos')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 px-1">
        <div class="flex flex-col">
            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Consola de Facturación SET</h1>
            <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold italic">Integración SIFEN v1.5.0 — Control de Documentos Electrónicos</p>
        </div>
        
        <div class="flex items-center gap-3">
             <div class="flex items-center gap-2 p-1 bg-surface2/40 backdrop-blur-md rounded-2xl border border-white/5 shadow-xl">
                <div class="px-4 py-2 flex items-center gap-2 border-r border-white/5">
                    <div class="w-2 h-2 rounded-full {{ $statusSifen['cert_ok'] ? 'bg-primary animate-pulse' : 'bg-red-500' }}"></div>
                    <span class="text-[0.6rem] font-black uppercase tracking-widest text-white/70">Firma Digital</span>
                </div>
                <div class="px-4 py-2 flex items-center gap-2">
                    <span class="text-[0.6rem] font-black uppercase tracking-widest {{ $statusSifen['ambiente'] === 'produccion' ? 'text-accent' : 'text-amber-500' }}">
                        {{ $statusSifen['ambiente'] === 'produccion' ? 'Producción' : 'Sandbox (Test)' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-primary/10 border border-primary/20 text-primary text-xs font-black uppercase tracking-widest flex items-center gap-3 animate-fade-in shadow-lg shadow-primary/5">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-black uppercase tracking-widest flex items-center gap-3 animate-fade-in shadow-lg shadow-red-500/5">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
            <div class="flex flex-col">
                <span class="font-black">ERROR DE TRANSMISIÓN</span>
                <span class="opacity-70 mt-1 lowercase font-mono tracking-normal">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- ── Status Matrix ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-8">
        {{-- RUC --}}
        <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 p-5 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/5 rounded-full blur-xl group-hover:scale-150 transition-transform"></div>
            <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-[0.2em] block mb-2">Identidad Fiscal</span>
            <div class="flex items-center gap-3">
                <div class="text-xl font-black text-white italic tracking-tighter">{{ $statusSifen['ruc_emisor'] }}</div>
                <span class="text-[0.5rem] font-bold text-muted-foreground uppercase bg-white/5 px-2 py-0.5 rounded border border-white/5">SET_PY</span>
            </div>
        </div>

        {{-- Timbrado --}}
        <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 p-5 relative overflow-hidden group">
            <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-[0.2em] block mb-2">Timbrado Vigente</span>
            <div class="flex items-center gap-3">
                <div class="text-xl font-black text-white italic tracking-tighter">{{ $statusSifen['numero_timbrado'] }}</div>
                <svg class="w-4 h-4 text-accent animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75 11.25 15 15 9.75m-3-10.457L17.5 1.5l5.25 3v10.5a10.5 10.5 0 0 1-5.25 9L12 25.5l-5.25-3a10.5 10.5 0 0 1-5.25-9V3.5l5.25-2 5.25 1.043Z" /></svg>
            </div>
        </div>

        {{-- Certificado --}}
        <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 p-5 relative overflow-hidden group col-span-1 lg:col-span-2">
            <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-[0.2em] block mb-2">Firma Digital (X.509)</span>
            <div class="flex items-center justify-between">
                <span class="text-[0.65rem] font-black uppercase italic {{ $statusSifen['cert_ok'] ? 'text-primary' : 'text-red-500' }}">
                    {{ $statusSifen['cert_ok'] ? 'Autorizado y Activo' : 'Certificado No Detectado' }}
                </span>
                <span class="text-[0.55rem] font-mono text-muted-foreground/40 hidden md:block select-all cursor-copy">{{ $statusSifen['cert_path'] }}</span>
            </div>
        </div>
    </div>

    {{-- ── Metrics & Control ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        {{-- Counters --}}
        <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('sifen.index', ['filtro' => 'pendientes']) }}" 
                class="erp-card !bg-surface/20 !backdrop-blur-xl !border-white/5 p-6 hover:!border-primary/40 transition-all group relative overflow-hidden {{ $filtro === 'pendientes' ? '!border-primary/60 shadow-lg shadow-primary/5' : '' }}">
                @if($filtro === 'pendientes') <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div> @endif
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em]">Queue Pendiente</span>
                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                </div>
                <div class="text-3xl font-black text-white italic tracking-tighter">{{ $totalPendientes }}<small class="text-xs opacity-30 not-italic ml-2 tracking-widest font-black">DOCS</small></div>
            </a>

            <a href="{{ route('sifen.index', ['filtro' => 'emitidas']) }}" 
                class="erp-card !bg-surface/20 !backdrop-blur-xl !border-white/5 p-6 hover:!border-accent/40 transition-all group relative overflow-hidden {{ $filtro === 'emitidas' ? '!border-accent/60 shadow-lg shadow-accent/5' : '' }}">
                @if($filtro === 'emitidas') <div class="absolute left-0 top-0 bottom-0 w-1 bg-accent"></div> @endif
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em]">Sincronizados OK</span>
                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-accent group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                </div>
                <div class="text-3xl font-black text-white italic tracking-tighter">{{ $totalEmitidas }}<small class="text-xs opacity-30 not-italic ml-2 tracking-widest font-black">FILES</small></div>
            </a>

            <a href="{{ route('sifen.index', ['filtro' => 'errores']) }}" 
                class="erp-card !bg-surface/20 !backdrop-blur-xl !border-white/5 p-6 hover:!border-red-500/40 transition-all group relative overflow-hidden {{ $filtro === 'errores' ? '!border-red-500/60 shadow-lg shadow-red-500/5' : '' }}">
                @if($filtro === 'errores') <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500"></div> @endif
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em]">Eventos de Error</span>
                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    </div>
                </div>
                <div class="text-3xl font-black text-white italic tracking-tighter">{{ $totalConError }}<small class="text-xs opacity-30 not-italic ml-2 tracking-widest font-black">LOGS</small></div>
            </a>
        </div>

        {{-- Batch Control --}}
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('sifen.reintentar') }}" onsubmit="return confirm('¿Iniciar procesamiento por lotes?')">
                @csrf
                <button type="submit" @disabled($totalPendientes == 0) class="w-full h-full min-h-[140px] erp-card !bg-primary/10 !border-primary/20 hover:!bg-primary/20 transition-all flex flex-col items-center justify-center gap-4 group p-6 shadow-xl shadow-primary/5 disabled:opacity-30 disabled:cursor-not-allowed">
                    <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center text-primary {{ $totalPendientes > 0 ? 'group-hover:rotate-180' : '' }} transition-transform duration-700">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    </div>
                    <div class="text-center">
                        <span class="text-[0.65rem] font-black text-white uppercase tracking-[0.2em] block">Sincronización Cloud</span>
                        <span class="text-[0.55rem] font-bold text-primary uppercase mt-1 block tracking-widest">Ejecutar Batch Pendiente</span>
                    </div>
                </button>
            </form>
        </div>
    </div>

    {{-- ── Main Ledger ── --}}
    <div class="erp-card !bg-surface/30 !backdrop-blur-xl !border-white/5 shadow-2xl relative overflow-hidden">
        <div class="erp-card-header !bg-transparent border-b border-white/5 p-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-2 h-6 bg-accent rounded-full mb-1"></div>
                <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-white italic">
                    Registro de Comprobantes Eletrónicos
                </h2>
            </div>
            <span class="text-[0.55rem] font-black text-muted-foreground uppercase bg-white/5 px-4 py-1.5 rounded-full border border-white/5 italic">
                {{ $ventas->total() }} Operaciones Detectadas
            </span>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="erp-table text-xs">
                <thead>
                    <tr class="text-[0.6rem] uppercase tracking-widest text-muted-foreground border-b border-white/5">
                        <th class="!pl-8">ID Operación</th>
                        <th>Receptor del Bien</th>
                        <th class="text-center">Tipo Doc</th>
                        <th class="text-center">Estado SIFEN</th>
                        @if($filtro === 'emitidas')
                            <th>Audit Trail (CDC)</th>
                        @elseif($filtro === 'errores')
                            <th>Descripción del Fallo</th>
                        @endif
                        <th class="text-right">Importe $</th>
                        <th class="text-center !pr-8">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($ventas as $v)
                        <tr class="hover:bg-white/5 group transition-all duration-200">
                            {{-- ID y Fecha --}}
                            <td class="!pl-8">
                                <div class="flex flex-col py-2">
                                    <span class="text-[0.7rem] font-black text-white italic uppercase tracking-tighter">{{ $v->numero_venta }}</span>
                                    <span class="text-[0.55rem] text-muted-foreground font-bold">{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d.m.Y') }}</span>
                                </div>
                            </td>

                            {{-- Cliente --}}
                            <td>
                                <div class="flex flex-col">
                                    <span class="text-[0.65rem] font-black text-white/70 uppercase italic tracking-tighter truncate max-w-[150px]">{{ $v->cliente?->razon_social ?? 'CONSUMIDOR FINAL' }}</span>
                                    <span class="text-[0.55rem] text-muted-foreground font-mono">{{ $v->cliente?->ruc ?? '0000000-0' }}</span>
                                </div>
                            </td>

                            {{-- Tipo Comp --}}
                            <td class="text-center">
                                <span class="text-[0.55rem] font-black px-2 py-0.5 rounded bg-white/5 text-muted-foreground uppercase tracking-widest border border-white/5">
                                    {{ $v->tipo_comprobante_sifen === '01' ? 'FE' : ($v->tipo_comprobante_sifen === '04' ? 'NCE' : 'FE') }}
                                </span>
                            </td>

                            {{-- Badge de Estado --}}
                            <td class="text-center">
                                @php
                                    $statusColor = match($v->estado_sifen) {
                                        'APROBADO' => 'text-accent bg-accent/10 border-accent/20',
                                        'RECHAZADO' => 'text-red-500 bg-red-500/10 border-red-500/20',
                                        'ERROR'    => 'text-red-400 bg-red-400/10 border-red-400/20',
                                        default    => 'text-muted-foreground bg-white/5 border-white/5'
                                    };
                                @endphp
                                <div class="flex items-center justify-center">
                                    <span class="text-[0.5rem] font-black px-2 py-1 rounded-lg border {{ $statusColor }} uppercase italic tracking-widest inline-flex items-center gap-1.5">
                                        <div class="w-1 h-1 rounded-full {{ str_contains($statusColor, 'accent') ? 'bg-accent animate-pulse' : 'bg-current opacity-50' }}"></div>
                                        {{ $v->estado_sifen ?? 'PENDIENTE' }}
                                    </span>
                                </div>
                            </td>
                            
                            {{-- CDC o Error --}}
                            @if($filtro === 'emitidas')
                                <td>
                                    <div class="flex items-center gap-2">
                                        <code class="text-[0.55rem] text-accent font-mono bg-accent/5 px-2 py-1 rounded border border-accent/10 cursor-help" title="{{ $v->cdc_sifen }}">
                                            {{ substr($v->cdc_sifen, 0, 8) }}...{{ substr($v->cdc_sifen, -8) }}
                                        </code>
                                    </div>
                                </td>
                            @elseif($filtro === 'errores')
                                <td>
                                    <span class="text-[0.55rem] font-bold text-red-500/80 uppercase italic truncate max-w-[200px] inline-block" title="{{ $v->sifen_error }}">
                                        {{ $v->sifen_error ?? 'Documento Rechazado por validación' }}
                                    </span>
                                </td>
                            @endif

                            {{-- Precio --}}
                            <td class="text-right">
                                <span class="text-[0.75rem] font-black text-white italic tracking-tighter">$ {{ number_format($v->precio_venta_usd, 0, ',', '.') }}</span>
                            </td>

                            {{-- Acciones --}}
                            <td class="text-center !pr-8">
                                <div class="flex items-center justify-center gap-2">
                                    @php
                                        $puedEmitir = !$v->tiene_factura_electronica || in_array($v->estado_sifen, ['RECHAZADO', 'ERROR', 'PENDIENTE', null]);
                                    @endphp

                                    @if($puedEmitir)
                                        {{-- Dropdown para emitir --}}
                                        <div class="relative group/menu">
                                            <button type="button" class="w-9 h-9 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white transition-all border border-primary/20 flex items-center justify-center shadow-lg hover:shadow-primary/20 active:scale-95 group/btn">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                            </button>
                                            
                                            <div class="absolute right-0 top-full mt-2 w-48 bg-surface2/90 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all z-50 p-2 overflow-hidden">
                                                <div class="text-[0.5rem] font-black text-muted-foreground uppercase tracking-widest px-3 py-2 border-b border-white/5 mb-1">Seleccionar Tipo</div>
                                                <form method="POST" action="{{ route('sifen.emitir', $v->id) }}">
                                                    @csrf
                                                    <input type="hidden" name="tipo" value="01">
                                                    <button type="submit" class="w-full truncate text-left px-3 py-2 text-[0.6rem] font-black text-white hover:bg-primary/20 rounded-xl transition-colors flex items-center gap-2">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                                        Factura Electrónica (01)
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('sifen.emitir', $v->id) }}">
                                                    @csrf
                                                    <input type="hidden" name="tipo" value="04">
                                                    <button type="submit" class="w-full truncate text-left px-3 py-2 text-[0.6rem] font-black text-white/70 hover:bg-white/5 rounded-xl transition-colors flex items-center gap-2">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-muted-foreground"></div>
                                                        Nota de Crédito (04)
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Ver PDF (KuDE) --}}
                                        <a href="{{ route('sifen.kude', $v->id) }}" target="_blank" class="w-9 h-9 rounded-xl bg-accent/10 hover:bg-accent text-accent hover:text-white transition-all border border-accent/20 flex items-center justify-center shadow-lg hover:shadow-accent/20 active:scale-95" title="Ver KuDE (PDF)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                        </a>
                                        {{-- Ver XML --}}
                                        <a href="{{ route('sifen.xml', $v->id) }}" target="_blank" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 text-white transition-all border border-white/10 flex items-center justify-center shadow-lg active:scale-95" title="Bajar XML (DE)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-24 text-muted-foreground/20 font-black uppercase italic tracking-[0.5em] text-xs">Sin registros sincronizados en esta categoría</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($ventas->hasPages())
            <div class="px-8 py-4 border-t border-white/5">
                {{ $ventas->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Script de Selección Dinámica --}}
    <script>
        function emitirDocumento(saleId, tipo) {
            const form = document.querySelector(`#form-emit-${saleId}`);
            if(!form) return;
            form.querySelector('input[name="tipo"]').value = tipo;
            if(confirm(`¿Transmitir documento tipo ${tipo === '01' ? 'Factura' : 'Nota'} a SIFEN?`)) {
                form.submit();
            }
        }
    </script>
@endsection
