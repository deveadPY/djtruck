@extends('layouts.app')
@section('title', 'Detalle de Proveedor — ERP Camiones')
@section('page-title', 'Perfil del Proveedor')

@section('content')    {{-- ── Cabecera y Acciones Rápidas ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('proveedores.index') }}" class="p-2.5 rounded-xl bg-surface2 border border-white/5 text-muted-foreground hover:text-primary transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase italic">{{ $proveedor->razon_social }}</h1>
                    @if($proveedor->activo)
                        <span class="badge-status badge-disponible !text-[0.6rem] !px-2 !font-black uppercase tracking-widest shadow-lg shadow-green-500/10">Vigor</span>
                    @else
                        <span class="badge-status badge-vendido !text-[0.6rem] !px-2 !font-black uppercase tracking-widest">Inactivo</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-5 h-3.5 rounded overflow-hidden shadow-sm border border-white/5 flex-shrink-0">
                        <img src="https://flagcdn.com/w40/{{ strtolower($proveedor->pais) }}.png" class="w-full h-full object-cover">
                    </div>
                    <p class="text-[0.65rem] text-muted-foreground uppercase tracking-[0.2em] font-bold">Registro Fiscal: {{ $proveedor->ruc_rut_nit ?: 'PENDIENTE' }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('proveedores.edit', $proveedor->id) }}" class="p-3 bg-surface2 border border-white/5 text-muted-foreground hover:text-white rounded-xl transition-all shadow-lg" title="Editar Perfil">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                </svg>
            </a>
            <form method="POST" action="{{ route('proveedores.destroy', $proveedor->id) }}" onsubmit="return confirm('¿Eliminar registro comercial?')">
                @csrf @method('DELETE')
                <button type="submit" class="p-3 bg-red-500/10 border border-red-500/20 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-all shadow-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Ficha de Identidad --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="erp-card !bg-surface/40 !backdrop-blur-md !border-white/5 relative overflow-hidden">
                <div class="absolute -top-10 -left-10 w-32 h-32 bg-primary/10 rounded-full blur-3xl"></div>
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Perfil Corporativo</h2>
                </div>
                <div class="erp-card-body p-6">
                    <div class="flex flex-col items-center mb-6">
                        <div class="w-20 h-20 rounded-3xl bg-primary/10 flex items-center justify-center text-primary border border-primary/10 shadow-inner mb-3">
                            <span class="text-3xl font-black uppercase italic">{{ substr($proveedor->razon_social, 0, 1) }}</span>
                        </div>
                        <h3 class="text-sm font-black text-white uppercase italic text-center">{{ $proveedor->razon_social }}</h3>
                        <span class="text-[0.6rem] text-muted-foreground font-black uppercase tracking-widest mt-1">{{ $proveedor->tipo }}</span>
                    </div>

                    <div class="space-y-4">
                        @foreach([
                            'Fantasía' => $proveedor->nombre_fantasia ?: '—',
                            'Jurisdicción' => $proveedor->pais,
                            'Moneda' => $proveedor->moneda_principal,
                            'Registro' => \Carbon\Carbon::parse($proveedor->created_at)->format('d/m/Y')
                        ] as $label => $val)
                            <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0 text-xs text-white/80">
                                <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest">{{ $label }}</span>
                                <span class="font-bold">{{ $val }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Contacto Card --}}
            <div class="erp-card !bg-surface/20 !backdrop-blur-md !border-white/5">
                <div class="erp-card-header !bg-transparent border-b border-white/5">
                    <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground">Canales de Contacto</h2>
                </div>
                <div class="erp-card-body p-5 space-y-4">
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-surface2/50 border border-white/5 group hover:bg-primary/5 transition-all">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest">Línea Directa</span>
                            <span class="text-xs font-bold text-white truncate">{{ $proveedor->telefono ?: 'No especificado' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-surface2/50 border border-white/5 group hover:bg-primary/5 transition-all">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-[0.55rem] font-black text-muted-foreground uppercase tracking-widest">Email Corporativo</span>
                            <span class="text-xs font-bold text-white truncate">{{ $proveedor->email ?: 'Sin email' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Historial Financiero --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="erp-card !border-white/5 relative overflow-hidden shadow-2xl">
                <div class="erp-card-header !bg-surface3/10 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-muted-foreground italic">Libro de Operaciones</h2>
                        <span class="px-2 py-0.5 rounded-full bg-surface2 text-[0.5rem] font-black text-primary border border-primary/20">{{ $facturas->count() }} TRANSACCIONES</span>
                    </div>
                    <a href="{{ route('facturas.create', ['proveedor_id' => $proveedor->id]) }}" class="text-[0.6rem] font-black text-primary hover:text-white transition-colors uppercase tracking-widest">+ Cargar Recibo</a>
                </div>
                
                {{-- Table (Desktop) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr class="text-[0.6rem] uppercase tracking-widest">
                                <th class="!pl-6">Emisión</th>
                                <th>Documento</th>
                                <th>Destino</th>
                                <th class="text-right">Total USD</th>
                                <th class="text-center">Estado</th>
                                <th class="text-right !pr-6"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($facturas as $f)
                                <tr class="hover:bg-primary/5 transition-all text-sm group cursor-pointer" onclick="window.location='{{ route('facturas.show', $f->id) }}'">
                                    <td class="!pl-6">
                                        <span class="font-bold text-white/80">{{ \Carbon\Carbon::parse($f->fecha_factura)->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        <div class="font-mono text-[0.65rem] font-black text-accent bg-surface2 px-2 py-1 rounded border border-white/5 w-fit">
                                            {{ $f->numero_factura }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="text-[0.6rem] font-black {{ $f->destino === 'VEHICULO' ? 'text-primary' : 'text-muted-foreground' }} uppercase tracking-tighter">
                                                {{ $f->destino === 'VEHICULO' ? '🚛 INVER. ACTIVO' : '🏢 GASTO LOCAL' }}
                                            </span>
                                            <span class="text-[0.6rem] text-muted-foreground font-bold truncate max-w-[150px]">
                                                @if($f->destino === 'VEHICULO' && $f->vehiculo_id)
                                                    {{ $f->marca }} {{ $f->modelo }}
                                                @else
                                                    {{ $f->cuenta_gasto ?: '—' }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <span class="font-black text-red-500 group-hover:scale-105 origin-right transition-transform inline-block">$ {{ number_format($f->total_usd, 2, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center">
                                         @php $cls = match ($f->estado) { 'APROBADA', 'PAGADA' => 'badge-disponible', 'ANULADA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                         <span class="badge-status {{ $cls }} !text-[0.5rem] !px-1.5 !font-black uppercase tracking-widest">{{ $f->estado }}</span>
                                    </td>
                                    <td class="text-right !pr-6">
                                        <svg class="w-4 h-4 text-muted-foreground group-hover:text-white transition-colors ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-20 text-muted-foreground/30 font-black uppercase tracking-widest text-xs italic">Sin historial financiero registrado</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Cards (Mobile) --}}
                <div class="md:hidden p-4 space-y-4">
                    @forelse($facturas as $f)
                        <div class="p-4 rounded-2xl bg-surface2/40 border border-white/5 space-y-3 shadow-xl" onclick="window.location='{{ route('facturas.show', $f->id) }}'">
                            <div class="flex items-center justify-between">
                                <span class="text-[0.6rem] font-black text-muted-foreground uppercase tracking-widest">{{ \Carbon\Carbon::parse($f->fecha_factura)->format('d/m/Y') }}</span>
                                @php $cls = match ($f->estado) { 'APROBADA', 'PAGADA' => 'badge-disponible', 'ANULADA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                <span class="badge-status {{ $cls }} !text-[0.5rem] !px-1.5 !font-black uppercase">{{ $f->estado }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[0.6rem] font-black text-primary uppercase tracking-tighter mb-1">FACTura #{{ $f->numero_factura }}</span>
                                <span class="text-xs font-bold text-white uppercase italic">
                                    @if($f->destino === 'VEHICULO') {{ $f->marca }} {{ $f->modelo }} @else {{ $f->cuenta_gasto ?: 'Gasto Operativo' }} @endif
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-white/5">
                                <span class="text-[0.5rem] font-black text-muted-foreground uppercase tracking-widest">Egreso USD</span>
                                <span class="text-sm font-black text-red-500">$ {{ number_format($f->total_usd, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-muted-foreground/30 font-black uppercase tracking-widest text-[0.6rem]">Sin movimientos</div>
                    @endforelse
                </div>
            </div>

            {{-- Documentos Adjuntos --}}
            <div class="mt-8">
                @include('partials.documentos', [
                    'documentos' => $documentos ?? collect(),
                    'documentableType' => 'proveedores',
                    'documentableId' => $proveedor->id,
                ])
            </div>
        </div>
    </div>
@endsection

