@extends('layouts.app')
@section('title', 'Detalle Factura')
@section('page-title', 'Detalle de Factura / Gasto')

@section('content')
    {{-- ── Cabecera Premium ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('facturas.index') }}" class="p-2.5 rounded-xl bg-surface2 border border-white/5 text-muted-foreground hover:text-primary transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">Documento #{{ $factura->numero_factura }}</h1>
                    @php $cls=match($factura->estado){'APROBADA','PAGADA'=>'badge-disponible','ANULADA'=>'badge-vendido',default=>'badge-preparacion'}; @endphp
                    <span class="badge-status {{ $cls }} !text-[0.6rem] !px-2 !font-black uppercase tracking-widest shadow-lg">{{ $factura->estado }}</span>
                </div>
                <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Fecha Contable: {{ \Carbon\Carbon::parse($factura->fecha_factura)->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if(isset($documentos) && $documentos->count() > 0)
                <a href="{{ route('documentos.download', $documentos->first()->id) }}" class="btn btn-primary flex-1 md:flex-none py-3 px-6 rounded-xl shadow-lg shadow-primary/25 border-b-2 border-primary-hover active:translate-y-0.5 transition-all text-xs font-black uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5L12 12l4.5-4.5M12 3v9" />
                    </svg>
                    Descargar Adjunto
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Datos del Gasto y Asociación --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5">
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Información del Emisor y Concepto</h2>
                </div>
                <div class="erp-card-body p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-surface2 flex items-center justify-center text-muted-foreground">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[0.6rem] text-muted-foreground uppercase font-black tracking-widest mb-0.5">Beneficiario / Proveedor</div>
                                    <div class="text-sm font-black text-white uppercase">{{ $factura->proveedor->razon_social ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="p-4 rounded-2xl bg-surface2 border border-white/5">
                                <span class="text-[0.55rem] text-muted-foreground uppercase font-black tracking-[0.2em] block mb-2">Concepto de Gasto</span>
                                <span class="text-xs font-bold text-white leading-relaxed">{{ $factura->cuenta_gasto ?? 'Sin especificar' }}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            {{-- Asociación de Vehículo --}}
                            @if($factura->destino === 'VEHICULO' && isset($factura->vehiculo))
                                <div class="group relative overflow-hidden p-5 rounded-3xl bg-accent/5 border border-accent/20 transition-all hover:bg-accent/10">
                                    <div class="absolute -top-6 -right-6 w-20 h-20 bg-accent/10 rounded-full blur-2xl group-hover:bg-accent/20 transition-all"></div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-9 h-9 rounded-xl bg-accent/20 flex items-center justify-center text-accent">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125v-10.5a1.125 1.125 0 0 0-1.125-1.125H3.375a1.125 1.125 0 0 0-1.125 1.125v4.5m17.25 2.25h-5.625c-.621 0-1.125.504-1.125 1.125v3.375m7.5-3.375h-7.5m7.5-1.125h-7.5" />
                                            </svg>
                                        </div>
                                        <span class="text-[0.6rem] font-black text-accent uppercase tracking-widest">Inversión Activo</span>
                                    </div>
                                    <a href="{{ route('vehicles.show', $factura->vehiculo->id) }}" class="block">
                                        <div class="text-xs font-black text-white hover:text-accent transition-colors mb-1 uppercase">{{ $factura->vehiculo->marca }} {{ $factura->vehiculo->modelo }}</div>
                                        <div class="font-mono text-[0.6rem] text-muted-foreground uppercase">Chasis: {{ $factura->vehiculo->numero_chasis }}</div>
                                    </a>
                                    <div class="mt-4 flex items-center gap-2 text-[0.55rem] font-bold text-accent/60 uppercase italic">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                        Impacto directo en valor libro
                                    </div>
                                </div>
                            @endif

                            {{-- Asociación de Reposición --}}
                            @if($factura->destino === 'REPOSICION' && $factura->compra_id)
                                <div class="group relative overflow-hidden p-5 rounded-3xl bg-amber-500/5 border border-amber-500/20 transition-all hover:bg-amber-500/10">
                                    <div class="absolute -top-6 -right-6 w-20 h-20 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-500">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path d="m20.25 7.5-.625 10.125a3.375 3.375 0 0 1-3.375 3.375H7.75a3.375 3.375 0 0 1-3.375-3.375L3.75 7.5M15 12a3 3 0 1 1-6 0m9.75-4.5h-13.5c-.612 0-1.118-.471-1.125-1.083L3.75 3h16.5l-.375 3.417c-.007.612-.513 1.083-1.125 1.083Z" />
                                            </svg>
                                        </div>
                                        <span class="text-[0.6rem] font-black text-amber-500 uppercase tracking-widest">Reposición Inventario</span>
                                    </div>
                                    <a href="{{ route('compras.show', $factura->compra_id) }}" class="block">
                                        <div class="text-xs font-black text-white hover:text-amber-500 transition-colors uppercase">Orden de Compra #{{ $factura->compra_id }}</div>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($factura->descripcion)
                        <div class="mt-8 pt-6 border-t border-white/5">
                            <h3 class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-[0.2em] mb-3">Observaciones / Notas Internas</h3>
                            <p class="text-[0.75rem] text-muted-foreground/80 leading-relaxed italic bg-surface2/50 p-4 rounded-2xl border border-white/5">
                                "{{ $factura->descripcion }}"
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar de Importes --}}
        <div class="space-y-6">
            <div class="erp-card !bg-red-500/5 !border-red-500/20 relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-red-500/10 rounded-full blur-3xl"></div>
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-red-400">Desglose Financiero</h2>
                </div>
                <div class="erp-card-body p-6 space-y-5">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[0.65rem] font-bold">
                            <span class="text-muted-foreground uppercase tracking-widest">Importe Neto</span>
                            <span class="text-white">{{ $factura->moneda }} {{ number_format($factura->subtotal, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[0.65rem] font-bold">
                            <span class="text-muted-foreground uppercase tracking-widest">Gravado (%)</span>
                            <span class="text-white">{{ $factura->moneda }} {{ number_format($factura->impuestos, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-white/10 text-center">
                        <span class="text-[0.55rem] text-muted-foreground uppercase font-black tracking-widest block mb-1">Monto de Egreso (Fiscal)</span>
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="text-xs font-black text-muted-foreground uppercase">{{ $factura->moneda }}</span>
                            <span class="text-3xl font-black text-white tracking-tighter">{{ number_format($factura->subtotal + $factura->impuestos, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 flex flex-col items-center">
                        <span class="text-[0.5rem] font-black text-red-400 uppercase tracking-widest mb-1">Equivalente Proyectado</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-xl font-black text-red-500 tracking-tighter">$ {{ number_format($factura->total_usd, 2, ',', '.') }}</span>
                            <span class="text-[0.6rem] font-black text-red-500 uppercase tracking-tighter">USD</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Documentos Adjuntos --}}
    <div class="mt-8">
        @include('partials.documentos', [
            'documentos' => $documentos ?? collect(),
            'documentableType' => 'facturas_proveedores',
            'documentableId' => $factura->id,
        ])
    </div>
@endsection
