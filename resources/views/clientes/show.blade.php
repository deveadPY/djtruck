@extends('layouts.app')
@section('title', 'Cliente')
@section('page-title', 'Detalle de Cliente')

@section('content')
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-start mb-6 flex-wrap gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('clientes.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Volver
            </a>
            <div>
                <h2 class="text-xl font-bold">{{ $cliente->razon_social }}</h2>
                <div class="text-xs mt-1" style="color:var(--text-muted)">
                    @if($cliente->nombre_fantasia)<span>{{ $cliente->nombre_fantasia }}</span> &nbsp;|&nbsp;@endif
                    RUC: <strong>{{ $cliente->ruc ?: 'No registrado' }}</strong> &nbsp;|&nbsp;
                    {{ $cliente->pais }} &nbsp;|&nbsp;
                    @if($cliente->activo)
                        <span class="text-green-500">● Activo</span>
                    @else
                        <span class="text-red-500">● Inactivo</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('clientes.estado-cuenta-pdf', $cliente->id) }}" class="btn btn-ghost" target="_blank">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                Estado de Cuenta PDF
            </a>
            <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                Editar
            </a>
            <a href="{{ route('ventas.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                Nueva Venta
            </a>
        </div>
    </div>

    {{-- Info cards row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
        {{-- Datos de contacto --}}
        <div class="erp-card">
            <div class="erp-card-header"><h2 class="flex items-center gap-2"><svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg> Datos de Contacto</h2></div>
            <div class="erp-card-body">
                @foreach([
                    'Email' => $cliente->email ?: 'No registrado',
                    'Teléfono' => $cliente->telefono ?: 'No registrado',
                    'Dirección' => $cliente->direccion ?: 'No registrada',
                ] as $label => $value)
                    <div class="flex justify-between py-2.5 border-b text-sm" style="border-color: var(--border);">
                        <span style="color:var(--text-muted)">{{ $label }}</span>
                        <span class="text-right max-w-[60%]">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Estado financiero --}}
        @php
            $linea_credito = $cliente->linea_credito_usd;
            $disponible = $linea_credito - $saldo_deudor;
            $porcentaje_uso = $linea_credito > 0 ? ($saldo_deudor / $linea_credito) * 100 : 0;
            $color_uso = $porcentaje_uso > 90 ? 'text-red-500' : ($porcentaje_uso > 75 ? 'text-amber-500' : 'text-green-500');
            $bar_color = $porcentaje_uso > 90 ? 'bg-red-500' : ($porcentaje_uso > 75 ? 'bg-amber-500' : 'bg-green-500');
        @endphp
        <div class="erp-card">
            <div class="erp-card-header"><h2 class="flex items-center gap-2"><svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg> Línea de Crédito</h2></div>
            <div class="erp-card-body">
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div class="rounded-xl p-4 border" style="background:var(--surface2);border-color:var(--border)">
                        <div class="text-[0.7rem] uppercase mb-1" style="color:var(--text-muted)">Deuda Activa</div>
                        <div class="text-2xl font-bold {{ $saldo_deudor > 0 ? 'text-red-500' : 'text-green-500' }}">$ {{ number_format($saldo_deudor, 2, ',', '.') }}</div>
                        <div class="text-[0.72rem]" style="color:var(--text-muted)">USD</div>
                    </div>
                    <div class="rounded-xl p-4 border" style="background:var(--surface2);border-color:var(--border)">
                        <div class="text-[0.7rem] uppercase mb-1" style="color:var(--text-muted)">Límite Aprobado</div>
                        <div class="text-2xl font-bold text-accent">$ {{ number_format($linea_credito, 2, ',', '.') }}</div>
                        <div class="text-[0.72rem]" style="color:var(--text-muted)">USD</div>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="rounded-full h-2 overflow-hidden mb-2" style="background:var(--surface3)">
                    <div class="{{ $bar_color }} h-full transition-all duration-300" style="width:{{ min(100, $porcentaje_uso) }}%"></div>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="{{ $color_uso }}">
                        @if($saldo_deudor > 0){{ number_format($porcentaje_uso, 1) }}% Utilizado @else Sin deuda actual @endif
                    </span>
                    <span style="color:var(--text-muted)">$ {{ number_format(max(0, $disponible), 2, ',', '.') }} USD Disponibles</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Historial de Ventas --}}
    <div class="erp-card mb-6">
        <div class="erp-card-header"><h2 class="flex items-center gap-2"><svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg> Historial de Ventas</h2></div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead><tr><th>Fecha</th><th>Nro. Venta</th><th>Vehículo</th><th>Monto (USD)</th><th>Estado</th><th class="w-20"></th></tr></thead>
                <tbody>
                    @forelse($ventas as $v)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}</td>
                            <td><strong class="text-accent">{{ $v->numero_venta }}</strong></td>
                            <td>{{ $v->marca }} {{ $v->modelo }}<br><span class="text-xs" style="color:var(--text-muted)">{{ $v->numero_chasis }}</span></td>
                            <td><strong>$ {{ number_format($v->precio_venta_usd, 2, ',', '.') }}</strong></td>
                            <td>@php $cls = match($v->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO' => 'badge-vendido', default => 'badge-preparacion' }; @endphp<span class="badge-status {{ $cls }}">{{ $v->estado }}</span></td>
                            <td><a href="{{ route('ventas.show', $v->id) }}" class="btn btn-ghost text-xs px-2 py-1">Ver →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-8" style="color:var(--text-muted)">No se encontraron ventas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Planes de Cuotas --}}
    <div class="erp-card mb-6">
        <div class="erp-card-header"><h2 class="flex items-center gap-2"><svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg> Planes de Cuotas Activos</h2></div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead><tr><th>Venta</th><th>Capital Financiado</th><th>Cuotas</th><th>Primera Cuota</th><th>Estado</th><th class="w-36"></th></tr></thead>
                <tbody>
                    @forelse($planes as $p)
                        <tr>
                            <td><strong class="text-accent">Venta #{{ $p->venta_id }}</strong></td>
                            <td><strong>$ {{ number_format($p->capital_total_usd, 2, ',', '.') }} USD</strong></td>
                            <td>{{ $p->numero_cuotas }} meses</td>
                            <td>{{ \Carbon\Carbon::parse($p->fecha_primera_cuota)->format('d/m/Y') }}</td>
                            <td>@php $cls = match($p->estado) { 'COMPLETADO' => 'badge-disponible', 'CANCELADO' => 'badge-vendido', default => 'badge-preparacion' }; @endphp<span class="badge-status {{ $cls }}">{{ $p->estado }}</span></td>
                            <td><a href="{{ route('planes_cuotas.show', $p->id) }}" class="btn btn-ghost text-xs px-2 py-1">Gestionar Pagos →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-8" style="color:var(--text-muted)">Sin planes de financiación.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Referencias --}}
    <div class="erp-card mb-6">
        <div class="erp-card-header">
            <h2 class="flex items-center gap-2">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                </svg>
                Referencias
            </h2>
            <button type="button" class="btn btn-primary text-xs"
                onclick="document.getElementById('ref-form-zone').style.display = document.getElementById('ref-form-zone').style.display === 'none' ? 'block' : 'none'">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Agregar Referencia
            </button>
        </div>

        {{-- Formulario de nueva referencia --}}
        <div id="ref-form-zone" style="display:none;border-bottom:1px solid var(--border)" class="p-4 sm:p-5">
            <form method="POST" action="{{ route('clientes.referencias.store', $cliente->id) }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-input" required>
                            <option value="COMERCIAL">Comercial</option>
                            <option value="PERSONAL">Personal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="nombre" class="form-input" required maxlength="150"
                            placeholder="Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Empresa / Ocupación</label>
                        <input type="text" name="empresa" class="form-input" maxlength="150"
                            placeholder="Empresa S.A. / Contador">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-input" maxlength="30"
                            placeholder="0981 123 456">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relación con el cliente</label>
                        <input type="text" name="relacion" class="form-input" maxlength="100"
                            placeholder="Socio, Familiar, Empleador...">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-input" rows="2" maxlength="500"
                            placeholder="Información adicional..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('ref-form-zone').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Referencia</button>
                </div>
            </form>
        </div>

        {{-- Lista de referencias --}}
        @if(isset($referencias) && count($referencias) > 0)
            @php
                $comerciales = $referencias->where('tipo', 'COMERCIAL');
                $personales  = $referencias->where('tipo', 'PERSONAL');
            @endphp
            <div class="p-4 sm:p-5">
                {{-- Comerciales --}}
                @if($comerciales->count())
                    <div class="mb-5">
                        <div class="text-[0.72rem] font-bold uppercase tracking-wider mb-3 flex items-center gap-2"
                            style="color:var(--text-muted)">
                            <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                            Referencias Comerciales
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($comerciales as $ref)
                                <div class="rounded-xl border p-4 relative" style="background:var(--surface2);border-color:var(--border)">
                                    <div class="font-semibold text-sm mb-1">{{ $ref->nombre }}</div>
                                    @if($ref->empresa)
                                        <div class="text-xs mb-0.5" style="color:var(--text-muted)">{{ $ref->empresa }}</div>
                                    @endif
                                    @if($ref->telefono)
                                        <div class="text-xs mb-0.5">
                                            <span style="color:var(--text-muted)">Tel:</span> {{ $ref->telefono }}
                                        </div>
                                    @endif
                                    @if($ref->relacion)
                                        <div class="text-xs mb-0.5">
                                            <span style="color:var(--text-muted)">Relación:</span> {{ $ref->relacion }}
                                        </div>
                                    @endif
                                    @if($ref->observaciones)
                                        <div class="text-xs mt-2 italic" style="color:var(--text-muted)">{{ $ref->observaciones }}</div>
                                    @endif
                                    <form method="POST"
                                        action="{{ route('clientes.referencias.destroy', [$cliente->id, $ref->id]) }}"
                                        class="absolute top-3 right-3"
                                        data-confirm="¿Eliminar esta referencia?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger text-xs px-2 py-1" title="Eliminar">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Personales --}}
                @if($personales->count())
                    <div>
                        <div class="text-[0.72rem] font-bold uppercase tracking-wider mb-3 flex items-center gap-2"
                            style="color:var(--text-muted)">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                            Referencias Personales
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($personales as $ref)
                                <div class="rounded-xl border p-4 relative" style="background:var(--surface2);border-color:var(--border)">
                                    <div class="font-semibold text-sm mb-1">{{ $ref->nombre }}</div>
                                    @if($ref->empresa)
                                        <div class="text-xs mb-0.5" style="color:var(--text-muted)">{{ $ref->empresa }}</div>
                                    @endif
                                    @if($ref->telefono)
                                        <div class="text-xs mb-0.5">
                                            <span style="color:var(--text-muted)">Tel:</span> {{ $ref->telefono }}
                                        </div>
                                    @endif
                                    @if($ref->relacion)
                                        <div class="text-xs mb-0.5">
                                            <span style="color:var(--text-muted)">Relación:</span> {{ $ref->relacion }}
                                        </div>
                                    @endif
                                    @if($ref->observaciones)
                                        <div class="text-xs mt-2 italic" style="color:var(--text-muted)">{{ $ref->observaciones }}</div>
                                    @endif
                                    <form method="POST"
                                        action="{{ route('clientes.referencias.destroy', [$cliente->id, $ref->id]) }}"
                                        class="absolute top-3 right-3"
                                        data-confirm="¿Eliminar esta referencia?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger text-xs px-2 py-1" title="Eliminar">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-8 text-sm" style="color:var(--text-muted)">
                Sin referencias registradas. Hacé clic en <strong>+ Agregar Referencia</strong> para cargar.
            </div>
        @endif
    </div>

    {{-- Documentos --}}
    @include('partials.documentos', [
        'documentos'       => $documentos ?? collect(),
        'documentableType' => 'clientes',
        'documentableId'   => $cliente->id,
        'tiposDocumento'   => ['CI' => 'Cédula de Identidad', 'RUC_IVA' => 'RUC / IVA', 'UBICACION' => 'Mapa / Ubicación', 'CONTRATO' => 'Contrato', 'OTRO' => 'Otro'],
    ])
@endsection