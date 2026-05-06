@extends('layouts.app')
@section('title', 'Detalle de Compra')
@section('page-title', 'Resumen de Compra #' . $compra->id)

@section('content')
<div class="mb-6">
    <a href="{{ route('compras.index') }}" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-muted-foreground hover:text-primary transition-colors group">
        <div class="p-2 rounded-lg bg-surface2 group-hover:bg-primary/10 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
        </div>
        Volver al Historial
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Columna Principal: Resumen e Ítems --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Card de Resumen con Estética Premium --}}
        <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-surface2/40 backdrop-blur-md">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
            
            <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10 border-b border-white/5">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shadow-inner">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .415.117.787.293 1.088.352.604.352 1.296 0 1.9-.176.301-.293.673-.293 1.088 0 .231.035.454.1.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5M16.5 4.5h1.875c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25H5.625a2.25 2.25 0 0 1-2.25-2.25V8.625c0-.621.504-1.125 1.125-1.125H5.25m9.903-3.09a1.012 1.012 0 0 1 1.507 0 1.012 1.012 0 0 1 0 1.508 1.012 1.012 0 0 1-1.507 0 1.012 1.012 0 0 1 0-1.508Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black tracking-tight text-white mb-0.5">Orden de Compra</h2>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-lg bg-surface font-mono text-xs font-bold text-accent border border-white/5 uppercase">#{{ $compra->numero_factura ?? 'S/N' }}</span>
                            <span class="badge-status {{ $compra->estado == 'COMPLETADO' ? 'status-completado' : 'status-pendiente' }} !text-[0.6rem] !px-2 !py-0.5">
                                {{ $compra->estado }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-1 gap-4 w-full md:w-auto text-right">
                    <div>
                        <span class="text-[0.6rem] text-muted-foreground uppercase font-black tracking-widest block">Fecha Emisión</span>
                        <span class="font-bold text-sm text-white">{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-surface2 text-muted-foreground">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[0.6rem] text-muted-foreground uppercase font-black tracking-widest block mb-0.5">Proveedor Seleccionado</span>
                            <span class="text-sm font-black text-white uppercase">{{ $compra->proveedor_nombre ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-surface2 text-muted-foreground">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[0.6rem] text-muted-foreground uppercase font-black tracking-widest block mb-0.5">Registrado por</span>
                            <span class="text-sm font-bold text-white uppercase tracking-tight">{{ $compra->usuario_nombre }}</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="p-4 rounded-2xl bg-surface/40 border border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-accent/20 flex items-center justify-center text-accent">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18v-.008Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                            </div>
                            <span class="text-[0.65rem] font-black uppercase tracking-widest text-muted-foreground">Fuente de Pago</span>
                        </div>
                        <span class="text-xs font-black text-white bg-surface rounded-lg px-3 py-1.5 border border-white/5">CAJA CAPITAL</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detalle de Ítems Optimizado --}}
        <div class="erp-card !bg-transparent !border-0 !shadow-none space-y-3">
            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-2 mt-4">Detalle de Productos ({{ count($items) }})</h3>
            
            {{-- Desktop Table --}}
            <div class="hidden md:block erp-card">
                <table class="erp-table text-xs">
                    <thead>
                        <tr class="bg-surface2/50 font-black uppercase tracking-widest text-[0.6rem]">
                            <th class="!pl-6">Código</th>
                            <th>Descripción</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-right">Precio C. (USD)</th>
                            <th class="text-right !pr-6">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr class="hover:bg-primary/5 transition-colors group">
                            <td class="!pl-6"><span class="font-mono font-bold text-accent">{{ $item->codigo }}</span></td>
                            <td class="font-bold text-white group-hover:text-primary transition-colors">{{ $item->descripcion }}</td>
                            <td class="text-center font-black bg-surface2/30">{{ number_format($item->cantidad, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold">{{ number_format($item->precio_compra_usd, 2, ',', '.') }}</td>
                            <td class="text-right !pr-6 font-mono font-black text-accent-light">$ {{ number_format($item->subtotal_usd, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden space-y-4">
                @foreach($items as $item)
                    <div class="rounded-2xl border border-white/5 bg-surface2/40 backdrop-blur-sm p-4 flex gap-4">
                        <div class="w-12 h-12 rounded-xl bg-surface/50 border border-white/5 flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-[0.45rem] font-black uppercase text-muted-foreground">Cant</span>
                            <span class="text-xs font-black text-accent">{{ number_format($item->cantidad, 0) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-[0.85rem] text-white leading-tight mb-1 truncate">{{ $item->descripcion }}</div>
                            <div class="flex justify-between items-end">
                                <div class="font-mono text-[0.65rem] font-black text-muted-foreground bg-surface px-1.5 py-0.5 rounded border border-white/5 uppercase">{{ $item->codigo }}</div>
                                <div class="text-right">
                                    <div class="text-[0.55rem] font-bold text-muted-foreground/60 uppercase">Unit. $ {{ number_format($item->precio_compra_usd, 2) }}</div>
                                    <div class="text-[0.85rem] font-black text-accent-light leading-none">$ {{ number_format($item->subtotal_usd, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Columna Lateral: Resumen Financiero --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-surface2/40 backdrop-blur-md p-6 md:p-8">
            <h3 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground/80 mb-6 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Liquidación Financiera
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2.5 border-b border-dashed border-white/5">
                    <span class="text-xs font-bold text-muted-foreground tracking-tight uppercase">Moneda Compra</span>
                    <span class="px-2 py-0.5 rounded bg-surface font-mono text-xs font-black text-white border border-white/5">{{ $compra->moneda_compra }}</span>
                </div>
                <div class="flex justify-between items-center py-2.5 border-b border-dashed border-white/5">
                    <span class="text-xs font-bold text-muted-foreground tracking-tight uppercase">Cotización</span>
                    <span class="font-mono text-xs font-bold text-white">{{ number_format($compra->tasa_cambio, 2, ',', '.') }} <span class="text-[0.6rem] opacity-30 italic">v/USD</span></span>
                </div>
                <div class="flex justify-between items-center py-2.5 border-b border-dashed border-white/5">
                    <span class="text-xs font-bold text-muted-foreground tracking-tight uppercase">Carga Original</span>
                    <div class="text-right">
                        <div class="font-mono text-[0.8rem] font-black text-white">{{ number_format($compra->monto_total_moneda, 2, ',', '.') }}</div>
                        <div class="text-[0.55rem] font-black uppercase text-muted-foreground opacity-50 tracking-widest leading-none">{{ $compra->moneda_compra }}</div>
                    </div>
                </div>

                <div class="mt-8 p-6 rounded-2xl bg-primary/10 border border-primary/20 relative group overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-20 h-20 text-primary" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.21 1.87 1.15 0 2.25-.54 2.25-1.53 0-.82-.56-1.36-2.25-1.9-2.32-.74-3.53-1.63-3.53-3.41 0-1.51 1.09-2.73 2.66-3.11V5h2.67v1.9c1.4.3 2.5 1.25 2.66 2.8h-1.96c-.15-.81-.71-1.49-1.9-1.49-1.12 0-1.89.54-1.89 1.34 0 .78.53 1.19 2.11 1.76 2.33.82 3.65 1.78 3.65 3.54a3.3 3.3 0 0 1-2.86 3.14z"/>
                        </svg>
                    </div>
                    <span class="text-[0.6rem] font-black uppercase tracking-[0.2em] text-primary/70 block mb-1">Inversión Final Realizada</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-primary tracking-tighter leading-none">$ {{ number_format($compra->monto_total_usd, 2, ',', '.') }}</span>
                        <span class="text-xs font-black text-primary/60 tracking-widest uppercase mb-1">USD</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Espacio para Observaciones o Notas si existieran --}}
        @isset($compra->observaciones)
            <div class="rounded-2xl border border-white/5 bg-surface2/40 backdrop-blur-sm p-6">
                <h4 class="text-[0.6rem] font-black uppercase tracking-widest text-muted-foreground mb-2">Observaciones Internas</h4>
                <p class="text-[0.75rem] text-white/70 italic leading-relaxed">"{{ $compra->observaciones }}"</p>
            </div>
        @endisset
    </div>
</div>

<div class="mt-8">
    @include('partials.documentos', [
        'documentos' => $documentos ?? collect(),
        'documentableType' => 'compras',
        'documentableId' => $compra->id,
    ])
</div>
@endsection
