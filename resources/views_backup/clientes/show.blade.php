@extends('layouts.app')
@section('title', 'Cliente — ERP Camiones')
@section('page-title', '👤 Detalle de Cliente')

@section('content')
    @if(session('success'))
        <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:var(--success);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <div style="display:flex;align-items:center;gap:1rem">
            <a href="{{ route('clientes.index') }}" class="btn btn-ghost">← Volver</a>
            <div>
                <h2 style="margin:0;font-size:1.3rem">{{ $cliente->razon_social }}</h2>
                <div style="font-size:.8rem;color:var(--text-muted);margin-top:.25rem">
                    @if($cliente->nombre_fantasia)
                        <span>{{ $cliente->nombre_fantasia }}</span> &nbsp;|&nbsp;
                    @endif
                    RUC: <strong>{{ $cliente->ruc ?: 'No registrado' }}</strong> &nbsp;|&nbsp;
                    {{ $cliente->pais }}
                    &nbsp;|&nbsp;
                    @if($cliente->activo)
                        <span style="color:var(--success)">● Activo</span>
                    @else
                        <span style="color:var(--danger)">● Inactivo</span>
                    @endif
                </div>
            </div>
        </div>
        <div style="display:flex;gap:.75rem">
            <a href="{{ route('clientes.estado-cuenta-pdf', $cliente->id) }}" class="btn btn-ghost" target="_blank"
               title="Descargar estado de cuenta en PDF">
                📄 Estado de Cuenta PDF
            </a>
            <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn btn-ghost">✏️ Editar</a>
            <a href="{{ route('ventas.create') }}" class="btn btn-primary">🛒 Nueva Venta</a>
        </div>
    </div>

    {{-- Info cards row --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem">
        {{-- Datos de contacto --}}
        <div class="card">
            <div class="card-header"><h2>📱 Datos de Contacto</h2></div>
            <div class="card-body">
                @foreach([
                    '✉️ Email' => $cliente->email ?: 'No registrado',
                    '📞 Teléfono' => $cliente->telefono ?: 'No registrado',
                    '📍 Dirección' => $cliente->direccion ?: 'No registrada',
                ] as $label => $value)
                    <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:.875rem">
                        <span style="color:var(--text-muted)">{{ $label }}</span>
                        <span style="text-align:right;max-width:60%">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Estado financiero --}}
        @php
            $linea_credito = $cliente->linea_credito_usd;
            $disponible = $linea_credito - $saldo_deudor;
            $porcentaje_uso = $linea_credito > 0 ? ($saldo_deudor / $linea_credito) * 100 : 0;
            $color_uso = $porcentaje_uso > 90 ? 'var(--danger)' : ($porcentaje_uso > 75 ? 'var(--warning)' : 'var(--success)');
        @endphp
        <div class="card">
            <div class="card-header"><h2>💳 Línea de Crédito</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem">
                        <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">Deuda Activa</div>
                        <div style="font-size:1.4rem;font-weight:700;color:{{ $saldo_deudor > 0 ? 'var(--danger)' : 'var(--success)' }}">$ {{ number_format($saldo_deudor, 2, ',', '.') }}</div>
                        <div style="font-size:.72rem;color:var(--text-muted)">USD</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem">
                        <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">Límite Aprobado</div>
                        <div style="font-size:1.4rem;font-weight:700;color:var(--accent)">$ {{ number_format($linea_credito, 2, ',', '.') }}</div>
                        <div style="font-size:.72rem;color:var(--text-muted)">USD</div>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div style="background:var(--surface1);border-radius:10px;height:8px;overflow:hidden;margin-bottom:.5rem">
                    <div style="background:{{ $color_uso }};height:100%;width:{{ min(100, $porcentaje_uso) }}%;transition:width .3s"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.82rem">
                    <span style="color:{{ $color_uso }}">
                        @if($saldo_deudor > 0)
                            {{ number_format($porcentaje_uso, 1) }}% Utilizado
                        @else
                            Sin deuda actual
                        @endif
                    </span>
                    <span style="color:var(--text-muted)">$ {{ number_format(max(0, $disponible), 2, ',', '.') }} USD Disponibles</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Historial de Ventas --}}
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><h2>🛒 Historial de Ventas</h2></div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nro. Venta</th>
                        <th>Vehículo</th>
                        <th>Monto (USD)</th>
                        <th>Estado</th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $v)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}</td>
                            <td><strong style="color:var(--accent)">{{ $v->numero_venta }}</strong></td>
                            <td>
                                {{ $v->marca }} {{ $v->modelo }}
                                <br><span style="font-size:.78rem;color:var(--text-muted)">{{ $v->numero_chasis }}</span>
                            </td>
                            <td><strong>$ {{ number_format($v->precio_venta_usd, 2, ',', '.') }}</strong></td>
                            <td>
                                @php $cls = match($v->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO' => 'badge-vendido', default => 'badge-preparacion' }; @endphp
                                <span class="badge-status {{ $cls }}">{{ $v->estado }}</span>
                            </td>
                            <td>
                                <a href="{{ route('ventas.show', $v->id) }}" class="btn btn-ghost" style="padding:.25rem .5rem;font-size:.75rem">Ver →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">No se encontraron ventas para este cliente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Planes de Cuotas --}}
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><h2>📅 Planes de Cuotas Activos</h2></div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Venta</th>
                        <th>Capital Financiado</th>
                        <th>Cuotas</th>
                        <th>Primera Cuota</th>
                        <th>Estado</th>
                        <th style="width:140px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($planes as $p)
                        <tr>
                            <td><strong style="color:var(--accent)">Venta #{{ $p->venta_id }}</strong></td>
                            <td><strong>$ {{ number_format($p->capital_total_usd, 2, ',', '.') }} USD</strong></td>
                            <td>{{ $p->numero_cuotas }} meses</td>
                            <td>{{ \Carbon\Carbon::parse($p->fecha_primera_cuota)->format('d/m/Y') }}</td>
                            <td>
                                @php $cls = match($p->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO' => 'badge-vendido', default => 'badge-preparacion' }; @endphp
                                <span class="badge-status {{ $cls }}">{{ $p->estado }}</span>
                            </td>
                            <td>
                                <a href="{{ route('planes_cuotas.show', $p->id) }}" class="btn btn-ghost" style="padding:.25rem .5rem;font-size:.75rem">Gestionar Pagos →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">El cliente no tiene planes de financiación registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Documentos --}}
    @include('partials.documentos', [
        'documentos' => $documentos ?? collect(),
        'documentableType' => 'clientes',
        'documentableId' => $cliente->id,
    ])
@endsection