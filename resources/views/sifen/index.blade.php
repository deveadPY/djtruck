@extends('layouts.app')
@section('title', 'SIFEN — Facturación Electrónica')
@section('page-title', 'SIFEN — Facturación Electrónica')

@section('content')

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    {{-- ── Estado del servicio ─────────────────────────────────────── --}}
    <div class="erp-card mb-5">
        <div class="erp-card-header">
            <h2>Estado del servicio SIFEN</h2>
            <span class="text-xs px-2 py-1 rounded-full font-semibold
                {{ $statusSifen['ambiente'] === 'produccion' ? 'bg-green-500/20 text-green-400' : 'bg-amber-500/20 text-amber-400' }}">
                {{ strtoupper($statusSifen['ambiente']) }}
            </span>
        </div>
        <div class="erp-card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <p class="form-label">RUC Emisor</p>
                    <p class="text-sm font-semibold" style="color:var(--text)">{{ $statusSifen['ruc_emisor'] }}</p>
                </div>
                <div>
                    <p class="form-label">N° Timbrado</p>
                    <p class="text-sm font-semibold" style="color:var(--text)">{{ $statusSifen['numero_timbrado'] }}</p>
                </div>
                <div>
                    <p class="form-label">Certificado digital</p>
                    <p class="text-sm font-semibold" style="color:{{ $statusSifen['cert_ok'] ? '#00d4aa' : '#ef4444' }}">
                        {{ $statusSifen['cert_ok'] ? '✓ Encontrado' : '✗ No encontrado' }}
                    </p>
                    <p class="text-xs mt-0.5" style="color:var(--text-muted)">{{ $statusSifen['cert_path'] }}</p>
                </div>
                <div>
                    <p class="form-label">Ambiente</p>
                    <p class="text-sm font-semibold" style="color:{{ $statusSifen['ambiente'] === 'produccion' ? '#00d4aa' : '#f59e0b' }}">
                        {{ $statusSifen['ambiente'] === 'produccion' ? 'PRODUCCIÓN' : 'SANDBOX (pruebas)' }}
                    </p>
                    @if($statusSifen['ambiente'] !== 'produccion')
                    <p class="text-xs mt-0.5" style="color:var(--text-muted)">Configura SIFEN_AMBIENTE=produccion en .env para producción</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Contadores ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <a href="{{ route('sifen.index', ['filtro' => 'pendientes']) }}" class="stat-card {{ $filtro === 'pendientes' ? 'primary' : '' }} no-underline">
            <div class="stat-label">Pendientes de emisión</div>
            <div class="stat-value">{{ $totalPendientes }}</div>
        </a>
        <a href="{{ route('sifen.index', ['filtro' => 'emitidas']) }}" class="stat-card {{ $filtro === 'emitidas' ? 'primary' : '' }} no-underline">
            <div class="stat-label">Facturas emitidas</div>
            <div class="stat-value" style="color:#00d4aa">{{ $totalEmitidas }}</div>
        </a>
        <a href="{{ route('sifen.index', ['filtro' => 'errores']) }}" class="stat-card {{ $filtro === 'errores' ? 'danger' : '' }} no-underline">
            <div class="stat-label">Con error</div>
            <div class="stat-value" style="color:#ef4444">{{ $totalConError }}</div>
        </a>
    </div>

    {{-- ── Acciones masivas ─────────────────────────────────────────── --}}
    @if($totalPendientes > 0)
    <div class="flex gap-3 mb-4">
        <form method="POST" action="{{ route('sifen.reintentar') }}" onsubmit="return confirm('¿Re-emitir hasta 10 facturas pendientes?')">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                Re-emitir pendientes (hasta 10)
            </button>
        </form>
    </div>
    @endif

    {{-- ── Listado ──────────────────────────────────────────────────── --}}
    <div class="erp-card">
        <div class="erp-card-header">
            <h2>
                @if($filtro === 'emitidas') Facturas emitidas
                @elseif($filtro === 'errores') Ventas con error SIFEN
                @else Ventas pendientes de emisión
                @endif
            </h2>
            <span class="text-xs" style="color:var(--text-muted)">{{ $ventas->total() }} registro(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>N° Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Monto USD</th>
                        @if($filtro === 'emitidas')
                            <th>CDC</th>
                            <th>Timbrado</th>
                        @elseif($filtro === 'errores')
                            <th>Error</th>
                        @endif
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $v)
                        <tr>
                            <td>
                                <a href="{{ route('ventas.show', $v->id) }}" style="color:var(--primary);text-decoration:none;font-weight:600;font-size:.8rem">
                                    {{ $v->numero_venta }}
                                </a>
                            </td>
                            <td style="font-size:.8rem">{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}</td>
                            <td style="font-size:.8rem">{{ $v->cliente?->razon_social ?? '—' }}</td>
                            <td style="font-size:.8rem">
                                {{ $v->vehiculo?->marca ?? '—' }} {{ $v->vehiculo?->modelo ?? '' }}
                            </td>
                            <td style="font-size:.82rem;font-weight:600">$ {{ number_format($v->precio_venta_usd, 0, ',', '.') }}</td>

                            @if($filtro === 'emitidas')
                                <td>
                                    <code style="font-size:.7rem;color:var(--accent)">
                                        {{ $v->cdc_sifen ? substr($v->cdc_sifen, 0, 20) . '...' : '—' }}
                                    </code>
                                </td>
                                <td style="font-size:.8rem">{{ $v->numero_timbrado ?? '—' }}</td>
                            @elseif($filtro === 'errores')
                                <td>
                                    <span class="text-xs" style="color:#ef4444;max-width:200px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                          title="{{ $v->sifen_error ?? '' }}">
                                        {{ $v->sifen_error ?? '—' }}
                                    </span>
                                </td>
                            @endif

                            <td>
                                @if(!$v->tiene_factura_electronica)
                                    <form method="POST" action="{{ route('sifen.emitir', $v->id) }}"
                                          onsubmit="return confirm('¿Emitir factura electrónica para {{ addslashes($v->numero_venta) }}?')"
                                          style="display:inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success" style="padding:.3rem .75rem;font-size:.75rem">
                                            Emitir
                                        </button>
                                    </form>
                                @else
                                    <span class="badge-status badge-disponible">Emitida</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2.5rem">
                                @if($filtro === 'emitidas') No hay facturas emitidas aún.
                                @elseif($filtro === 'errores') No hay ventas con error.
                                @else No hay ventas pendientes de emisión.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ventas->hasPages())
            <div class="p-4">{{ $ventas->appends(request()->query())->links() }}</div>
        @endif
    </div>

@endsection
