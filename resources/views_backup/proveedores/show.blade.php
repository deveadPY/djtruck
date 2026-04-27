@extends('layouts.app')
@section('title', 'Detalle de Proveedor — ERP Camiones')
@section('page-title', '🏢 Perfil del Proveedor')

@section('content')
    <div style="margin-bottom:1rem">
        <a href="{{ route('proveedores.index') }}" class="btn btn-ghost">← Volver al listado</a>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('proveedores.edit', $proveedor->id) }}" class="btn btn-ghost" style="padding:.4rem .8rem;font-size:.875rem">✏️ Editar</a>
            <form method="POST" action="{{ route('proveedores.destroy', $proveedor->id) }}" style="display:inline" onsubmit="return confirm('¿Eliminar proveedor de forma permanente?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost" style="padding:.4rem .8rem;font-size:.875rem;color:var(--danger)">🗑️ Eliminar</button>
            </form>
        </div>
    </div>
    
    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-header">
            <div style="display:flex;align-items:center;gap:1rem">
                <h2>{{ $proveedor->razon_social }}</h2>
                @if($proveedor->activo)
                    <span class="badge-status badge-disponible">Activo</span>
                @else
                    <span class="badge-status badge-vendido">Inactivo</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem">
                <div>
                    <h3 style="font-size:.8rem; color:var(--text-muted); margin-bottom:1rem; text-transform:uppercase">Datos Generales</h3>
                    @foreach([
                            'Tipo' => $proveedor->tipo,
                            'RUC/NIT/RUT' => $proveedor->ruc_rut_nit ?? '—',
                            'Nombre Fantasía' => $proveedor->nombre_fantasia ?? '—',
                            'País' => $proveedor->pais,
                        ] as $label => $value)
                        <div style="display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid var(--border); font-size:.875rem">
                            <span style="color:var(--text-muted)">{{ $label }}</span>
                            <span style="font-weight: 500">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
                <div>
                    <h3 style="font-size:.8rem; color:var(--text-muted); margin-bottom:1rem; text-transform:uppercase">Contacto y Finanzas</h3>
                    @foreach([
                            'Teléfono' => $proveedor->telefono ?? '—',
                            'Email' => $proveedor->email ?? '—',
                            'Moneda Principal' => $proveedor->moneda_principal,
                            'Fecha de Registro' => \Carbon\Carbon::parse($proveedor->created_at)->format('d/m/Y'),
                        ] as $label => $value)
                        <div style="display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid var(--border); font-size:.875rem">
                            <span style="color:var(--text-muted)">{{ $label }}</span>
                            <span style="font-weight: 500">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Facturas -->
    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center">
            <h2>🧾 Historial de Facturas y Gastos</h2>
            <a href="{{ route('facturas.create', ['proveedor_id' => $proveedor->id]) }}" class="btn btn-ghost" style="padding:.4rem .8rem;font-size:.875rem;color:var(--accent)">+ Cargar Factura</a>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>N° Factura</th>
                        <th>Destino</th>
                        <th>Referencia</th>
                        <th>Total USD</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facturas as $f)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($f->fecha_factura)->format('d/m/Y') }}</td>
                            <td><strong>{{ $f->numero_factura }}</strong></td>
                            <td>
                                @if($f->destino === 'VEHICULO')
                                    <span style="color:var(--accent);font-weight:600">🚛 Vehículo</span>
                                @elseif($f->destino === 'GASTO_OPERATIVO')
                                    <span style="color:var(--text-muted);font-weight:600">🏢 Local</span>
                                @else
                                    <span style="color:var(--text);font-weight:600">Mixto</span>
                                @endif
                            </td>
                            <td>
                                @if($f->destino === 'VEHICULO' && $f->vehiculo_id)
                                    <a href="{{ route('vehicles.show', $f->vehiculo_id) }}" style="color:var(--text)">
                                        {{ $f->marca }} {{ $f->modelo }} (Chasis: {{ substr($f->numero_chasis, -6) }})
                                    </a>
                                @else
                                    {{ $f->cuenta_gasto ?? '—' }}
                                @endif
                            </td>
                            <td style="color:var(--danger);font-weight:600">$ {{ number_format($f->total_usd, 2, ',', '.') }}</td>
                            <td>
                                @php $cls = match ($f->estado) { 'APROBADA', 'PAGADA' => 'badge-disponible', 'ANULADA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                <span class="badge-status {{ $cls }}">{{ $f->estado }}</span>
                            </td>
                            <td><a href="{{ route('facturas.show', $f->id) }}" class="btn btn-ghost"
                                    style="padding:.3rem .6rem;font-size:.75rem">Ver →</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">
                                Este proveedor aún no tiene facturas o gastos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gestión de Documentos -->
    @include('partials.documentos', [
        'documentos' => $documentos ?? collect(),
        'documentableType' => 'proveedores',
        'documentableId' => $proveedor->id,
    ])

@endsection
