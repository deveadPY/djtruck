@extends('layouts.app')
@section('title', 'Nueva Venta')
@section('page-title', '💰 Registrar Nueva Venta')

@push('styles')
<style>
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem}
.form-group{display:flex;flex-direction:column;gap:.4rem}
label{font-size:.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em}
input,select,textarea{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:.65rem .9rem;color:var(--text);font-family:inherit;font-size:.875rem;outline:none;width:100%;transition:border-color .2s}
input:focus,select:focus,textarea:focus{border-color:var(--primary)}
.step-indicator{display:flex;gap:0;margin-bottom:1.5rem}
.step-item{flex:1;text-align:center;padding:.75rem .5rem;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);background:var(--surface2);border-bottom:3px solid var(--border);cursor:pointer;transition:all .2s}
.step-item.active{color:var(--primary);border-bottom-color:var(--primary);background:var(--surface1)}
.step-item.done{color:var(--success);border-bottom-color:var(--success)}
.step-content{display:none}
.step-content.active{display:block}
.vehicle-card{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:1rem;cursor:pointer;transition:all .2s}
.vehicle-card:hover{border-color:var(--primary);transform:translateY(-2px)}
.vehicle-card.selected{border-color:var(--primary);box-shadow:0 0 0 2px var(--primary);background:rgba(99,102,241,.08)}
.payment-option{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem;cursor:pointer;transition:all .2s;text-align:center}
.payment-option:hover{border-color:var(--primary)}
.payment-option.selected{border-color:var(--primary);box-shadow:0 0 0 2px var(--primary);background:rgba(99,102,241,.08)}
.payment-entry{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:.75rem}
.mode-toggle{display:flex;gap:0;border-radius:10px;overflow:hidden;border:1px solid var(--border)}
.mode-toggle button{flex:1;padding:.75rem 1rem;border:none;background:var(--surface2);color:var(--text-muted);font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s}
.mode-toggle button.active{background:var(--primary);color:#fff}
.cuota-row{display:grid;grid-template-columns:30px 1fr 1fr 1fr auto;gap:.5rem;align-items:center;margin-bottom:.5rem}
.cuota-row input,.cuota-row select{padding:.5rem .6rem;font-size:.8rem}
</style>
@endpush

@section('content')
@if($errors->any())<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">{{ $errors->first() }}</div>@endif

<div style="margin-bottom:1rem"><a href="{{ route('ventas.index') }}" class="btn btn-ghost">← Volver</a></div>

{{-- Step Indicator --}}
<div class="step-indicator" style="border-radius:10px;overflow:hidden">
    <div class="step-item active" data-step="1" onclick="goToStep(1)">1. 🚛 Vehículo</div>
    <div class="step-item" data-step="2" onclick="goToStep(2)">2. 👤 Cliente</div>
    <div class="step-item" data-step="3" onclick="goToStep(3)">3. 💵 Precio y Pago</div>
    <div class="step-item" data-step="4" onclick="goToStep(4)">4. ✅ Confirmar</div>
</div>

<form method="POST" action="{{ route('ventas.store') }}" id="ventaForm">
    @csrf

    {{-- ═══════════════════════════════════ STEP 1: VEHÍCULO ═══════════════════════════════════ --}}
    <div class="step-content active" id="step1">
        <div class="card">
            <div class="card-header"><h2>🚛 Seleccioná el Vehículo</h2></div>
            <div class="card-body">
                <input type="hidden" name="vehiculo_id" id="vehiculo_id_input" value="{{ old('vehiculo_id') }}" required>

                <div style="margin-bottom:1rem">
                    <input type="text" id="buscarVehiculo" placeholder="🔍 Buscar por marca, modelo o chasis..."
                           style="background:var(--surface1)">
                </div>

                <div id="vehiculosGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
                    @foreach($vehiculos as $v)
                    <div class="vehicle-card" data-id="{{ $v->id }}"
                         data-costo="{{ $v->costo_origen_usd + ($v->total_gastos_usd ?? 0) }}"
                         data-precio-sugerido="{{ $v->precio_venta_sugerido_usd ?? 0 }}"
                         data-marca="{{ $v->marca }}" data-modelo="{{ $v->modelo }}"
                         data-chasis="{{ $v->numero_chasis }}" data-año="{{ $v->año }}"
                         data-estado="{{ $v->estado }}" data-color="{{ $v->color }}"
                         data-km="{{ $v->kilometraje }}"
                         onclick="seleccionarVehiculo(this)">
                        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:.5rem">
                            <div>
                                <div style="font-weight:700;font-size:1rem">{{ $v->marca }} {{ $v->modelo }}</div>
                                <div style="font-size:.78rem;color:var(--text-muted)">{{ $v->año }} · {{ $v->color ?? 'N/D' }}</div>
                            </div>
                            @php $cls = match($v->estado) { 'DISPONIBLE' => 'badge-disponible', 'RESERVADO' => 'badge-preparacion', default => 'badge-toma' }; @endphp
                            <span class="badge-status {{ $cls }}" style="font-size:.65rem">{{ $v->estado }}</span>
                        </div>
                        <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.5rem">
                            Chasis: <strong>{{ $v->numero_chasis }}</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;padding-top:.5rem;border-top:1px solid var(--border)">
                            <span style="color:var(--text-muted)">{{ number_format($v->kilometraje, 0, ',', '.') }} km</span>
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.25rem">
                                @if(($v->precio_venta_sugerido_usd ?? 0) > 0)
                                <span style="color:var(--success);font-weight:600" title="Precio Sugerido">$ {{ number_format($v->precio_venta_sugerido_usd, 2, ',', '.') }}</span>
                                @else
                                <span style="color:var(--accent);font-weight:600" title="Sin Precio Sugerido">—</span>
                                @endif
                                <span style="font-size:.7rem;color:var(--text-muted)">Libro: $ {{ number_format($v->costo_origen_usd + ($v->total_gastos_usd ?? 0), 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($vehiculos->isEmpty())
                <div style="text-align:center;color:var(--text-muted);padding:2rem">No hay vehículos disponibles para la venta.</div>
                @endif

                <div style="display:flex;justify-content:flex-end;margin-top:1.5rem">
                    <button type="button" class="btn btn-primary" onclick="goToStep(2)" id="btnStep1Next" disabled>Siguiente → Cliente</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════ STEP 2: CLIENTE ═══════════════════════════════════ --}}
    <div class="step-content" id="step2">
        <div class="card">
            <div class="card-header">
                <h2>👤 Seleccioná el Cliente</h2>
                <button type="button" class="btn btn-ghost" onclick="abrirModalCliente()" style="font-size:.8rem">+ Nuevo Cliente</button>
            </div>
            <div class="card-body">
                <input type="hidden" name="cliente_id" id="cliente_id_input" value="{{ old('cliente_id') }}" required>

                <div style="margin-bottom:1rem">
                    <input type="text" id="buscarCliente" placeholder="🔍 Buscar por nombre, RUC..."
                           style="background:var(--surface1)">
                </div>

                <div id="clientesGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:.75rem">
                    @foreach($clientes as $c)
                    <div class="vehicle-card" data-id="{{ $c->id }}"
                         data-nombre="{{ $c->razon_social }}" data-ruc="{{ $c->ruc }}"
                         data-telefono="{{ $c->telefono }}" data-pais="{{ $c->pais }}"
                         onclick="seleccionarCliente(this)">
                        <div style="font-weight:600;margin-bottom:.25rem">{{ $c->razon_social }}</div>
                        <div style="font-size:.78rem;color:var(--text-muted)">
                            RUC: {{ $c->ruc ?: 'N/A' }} · {{ $c->pais }}
                            @if($c->telefono) · 📞 {{ $c->telefono }} @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <div style="display:flex;justify-content:space-between;margin-top:1.5rem">
                    <button type="button" class="btn btn-ghost" onclick="goToStep(1)">← Vehículo</button>
                    <button type="button" class="btn btn-primary" onclick="goToStep(3)" id="btnStep2Next" disabled>Siguiente → Pago</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════ STEP 3: PRECIO Y PAGO ═══════════════════════════════════ --}}
    <div class="step-content" id="step3">
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header"><h2>💵 Precio de Venta</h2></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha de venta *</label>
                        <input type="date" name="fecha_venta" value="{{ old('fecha_venta', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Moneda de venta *</label>
                        <select name="moneda_venta" required onchange="calcularTotalVentaUsd()">
                            @foreach(['USD','PYG','BRL'] as $m)
                            <option value="{{ $m }}" {{ old('moneda_venta','USD')==$m?'selected':'' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Precio en moneda *</label>
                        <input type="number" name="precio_venta_moneda" value="{{ old('precio_venta_moneda') }}" step="0.01" min="0" required id="precio_moneda" oninput="calcularTotalVentaUsd()">
                    </div>
                    <div class="form-group">
                        <label>Precio en USD *</label>
                        <input type="number" name="precio_venta_usd" value="{{ old('precio_venta_usd') }}" step="0.01" min="0" required id="precio_usd" oninput="calcRent()">
                    </div>
                    <div class="form-group">
                        <label>Descuento (moneda)</label>
                        <input type="number" name="descuento_moneda" value="{{ old('descuento_moneda', 0) }}" step="0.01" min="0" id="descuento_moneda" oninput="calcularTotalVentaUsd()">
                    </div>
                    <div class="form-group">
                        <label>Descuento (USD)</label>
                        <input type="number" name="descuento_usd" value="{{ old('descuento_usd', 0) }}" step="0.01" min="0" id="descuento_usd" oninput="calcRent()">
                    </div>
                    <input type="hidden" name="tasa_cambio_venta" value="{{ old('tasa_cambio_venta', 1) }}">
                    <input type="hidden" name="estado" value="COMPLETADO">
                </div>

                <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.3);border-radius:10px;padding:1rem;margin-top:1.25rem;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:.85rem;font-weight:600;color:var(--primary);text-transform:uppercase">Precio Final (USD)</span>
                    <strong style="font-size:1.4rem;color:var(--primary)" id="precio_final_display">$ 0.00</strong>
                </div>

                {{-- Valor libro info --}}
                <div id="valor_libro_info" style="margin-top:1rem;display:none;background:var(--surface2);border:1px solid var(--border);padding:.75rem 1rem;border-radius:10px;font-size:.85rem">
                    📊 Valor libro: <strong style="color:var(--accent)" id="valor_libro_span">—</strong> USD &nbsp;|&nbsp;
                    Rentabilidad: <strong id="rent_span">—</strong>
                </div>
            </div>
        </div>

        {{-- Mode toggle: Contado vs Cuotas --}}
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header"><h2>📋 Modalidad de Pago</h2></div>
            <div class="card-body">
                <div class="mode-toggle" style="margin-bottom:1.25rem">
                    <button type="button" class="active" onclick="setPaymentMode('CONTADO')">💵 Contado</button>
                    <button type="button" onclick="setPaymentMode('CUOTAS')">📅 Plan de Cuotas</button>
                </div>
                <input type="hidden" name="modalidad_pago" id="modalidad_pago" value="CONTADO">

                <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem" id="texto_pagos">
                    Registrá los pagos recibidos al contado.
                </div>

                <div id="pagos_container">
                    {{-- First payment row (always visible) --}}
                    <div class="payment-entry" id="pago_row_0">
                        <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end">
                            <div class="form-group">
                                <label>Tipo de pago</label>
                                <select name="pagos[0][tipo]" onchange="toggleCamposPago(0, this.value)">
                                    <option value="EFECTIVO">💵 Efectivo</option>
                                    <option value="TRANSFERENCIA">🏦 Transferencia</option>
                                    <option value="VEHICULO_CANJE">🚗 Vehículo parte de pago</option>
                                    <option value="CHEQUE">📝 Cheque</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Monto USD</label>
                                <input type="number" name="pagos[0][monto_usd]" step="0.01" min="0" class="pago-monto" oninput="calcularTotalPagos()">
                            </div>
                            <div class="form-group" id="ref_container_0" style="display:none">
                                <label>Referencia / Banco</label>
                                <input type="text" name="pagos[0][referencia]" placeholder="Nro. transferencia...">
                            </div>
                            <div class="form-group" id="canje_container_0" style="display:none">
                                <label>Vehículo en Canje</label>
                                <select name="pagos[0][vehiculo_canje_id]">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($vehiculos_canje ?? [] as $vc)
                                    <option value="{{ $vc->id }}">{{ $vc->marca }} {{ $vc->modelo }} — {{ $vc->numero_chasis }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div></div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-ghost" onclick="agregarPago()" style="font-size:.8rem;margin-bottom:1rem">
                    + Agregar otro pago / entrega
                </button>

                <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem;display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                    <div>
                        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase" id="label_total_pagos">Total pagos registrados</div>
                        <div style="font-size:1.3rem;font-weight:700;color:var(--accent)" id="total_pagos_display">$ 0,00</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase" id="label_saldo">Saldo pendiente</div>
                        <div style="font-size:1.3rem;font-weight:700" id="saldo_pendiente_display">$ 0,00</div>
                    </div>
                </div>

                {{-- === PLAN DE CUOTAS CONFIG === --}}
                <div id="seccion_plan_cuotas" style="display:none; padding-top:1rem; border-top:1px solid var(--border)">
                    <h3 style="margin-top:0;margin-bottom:1rem;font-size:1rem">⚙️ Configuración del Plan de Cuotas</h3>
                    
                    <div style="background:var(--surface2);border-radius:8px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.85rem;display:flex;gap:1.5rem">
                        <div>Precio Base: <strong id="plan_precio_base">—</strong></div>
                        <div style="color:var(--danger)">Descuento: <strong id="plan_descuento">—</strong></div>
                        <div style="color:var(--primary)">Precio Final: <strong id="plan_precio_final">—</strong></div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo de plan *</label>
                            <select name="tipo_plan" id="tipo_plan" onchange="onTipoPlanChange()">
                                <option value="FRANCESA">Francesa (cuota fija)</option>
                                <option value="ALEMANA">Alemana (capital fijo)</option>
                                <option value="MANUAL" selected>Manual / Personalizado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capital a financiar (USD)</label>
                            <input type="number" id="capital_total_usd_visual" step="0.01" min="0" readonly style="background:var(--surface1);cursor:not-allowed">
                            <input type="hidden" name="capital_total_usd" id="capital_usd_input">
                        </div>
                    </div>

                    <div id="auto-config" style="display:none; margin-top:1rem">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Número de cuotas *</label>
                                <input type="number" name="numero_cuotas" id="numero_cuotas" value="12" min="1" max="120">
                            </div>
                            <div class="form-group">
                                <label>Tasa de interés mensual (%)</label>
                                <input type="number" name="tasa_interes_mensual" value="0" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label>Fecha primera cuota *</label>
                                <input type="date" name="fecha_primera_cuota" value="{{ \Carbon\Carbon::now()->addMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="form-group">
                                <label>Refuerzo cada N meses (opcional)</label>
                                <input type="number" name="refuerzo_cada" id="refuerzo_cada" min="0" max="12" value="0" placeholder="0 = sin refuerzo">
                            </div>
                            <div class="form-group">
                                <label>Monto de cada refuerzo (USD)</label>
                                <input type="number" name="refuerzo_monto" id="refuerzo_monto" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div id="manual-config" style="margin-top:1rem">
                        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.75rem">
                            Agregá filas de cuotas manualmente. Podés mezclar cuotas regulares y refuerzos.
                        </p>
                        <div style="font-size:.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:.5rem;display:grid;grid-template-columns:30px 1fr 1fr 1fr auto;gap:.5rem">
                            <span>#</span><span>Fecha Venc.</span><span>Tipo</span><span>Monto (USD)</span><span></span>
                        </div>
                        <div id="cuotas-container"></div>
                        <div style="display:flex; gap:.5rem; margin-top:.5rem">
                            <button type="button" class="btn btn-ghost" onclick="addCuotaManual('REGULAR')" style="font-size:.8rem;padding:.4rem .8rem;background:var(--surface2)">+ Cuota Regular</button>
                            <button type="button" class="btn btn-ghost" onclick="addCuotaManual('REFUERZO')" style="font-size:.8rem;padding:.4rem .8rem;background:rgba(245,158,11,.1);color:var(--warning)">⚡ Cuota Refuerzo</button>
                        </div>
                        <div style="background:var(--surface1);border-radius:8px;padding:.75rem;display:flex;justify-content:space-between;align-items:center;margin-top:1rem">
                            <div style="font-size:.8rem">Total Cuotas: <strong id="total_cuotas_sum" style="color:var(--accent)">$ 0.00</strong></div>
                            <div style="font-size:.8rem">Diferencia VS Capital: <strong id="diferencia_cuotas">$ 0.00</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="2" style="background:var(--surface2)">{{ old('observaciones') }}</textarea>
        </div>

        <div style="display:flex;justify-content:space-between">
            <button type="button" class="btn btn-ghost" onclick="goToStep(2)">← Cliente</button>
            <button type="button" class="btn btn-primary" onclick="goToStep(4)">Siguiente → Confirmar</button>
        </div>
    </div>

    {{-- ═══════════════════════════════════ STEP 4: CONFIRMACIÓN ═══════════════════════════════════ --}}
    <div class="step-content" id="step4">
        <div class="card">
            <div class="card-header"><h2>✅ Resumen de la Venta</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem">
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:1.25rem">
                        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.5rem">🚛 Vehículo</div>
                        <div style="font-weight:700;font-size:1rem" id="resumen_vehiculo">—</div>
                        <div style="font-size:.78rem;color:var(--text-muted)" id="resumen_chasis">—</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:1.25rem">
                        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.5rem">👤 Cliente</div>
                        <div style="font-weight:700;font-size:1rem" id="resumen_cliente">—</div>
                        <div style="font-size:.78rem;color:var(--text-muted)" id="resumen_cliente_ruc">—</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.5rem">
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem;text-align:center">
                        <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">Precio</div>
                        <div style="font-size:1.2rem;font-weight:700;color:var(--accent)" id="resumen_precio">$0</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem;text-align:center">
                        <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">Modalidad</div>
                        <div style="font-size:1.2rem;font-weight:700" id="resumen_modalidad">Contado</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem;text-align:center">
                        <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.25rem">Rentabilidad</div>
                        <div style="font-size:1.2rem;font-weight:700" id="resumen_rentabilidad">$0</div>
                    </div>
                </div>

                <div id="resumen_pagos_box" style="margin-bottom:1.5rem"></div>

                <div style="display:flex;justify-content:space-between">
                    <button type="button" class="btn btn-ghost" onclick="goToStep(3)">← Volver a editar</button>
                    <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:.75rem 2rem">💾 Confirmar y Registrar Venta</button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- ═══════ Modal Nuevo Cliente ═══════ --}}
<div id="modalCliente" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:9999">
    <div style="background:var(--surface1);width:90%;max-width:600px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.2);overflow:hidden">
        <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0">👤 Nuevo Cliente</h3>
            <button type="button" onclick="cerrarModalCliente()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted)">&times;</button>
        </div>
        <div style="padding:1.5rem;max-height:70vh;overflow-y:auto">
            <form id="formModalCliente">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1"><label>Razón Social *</label><input type="text" name="razon_social" required></div>
                    <div class="form-group"><label>RUC / CI</label><input type="text" name="ruc"></div>
                    <div class="form-group">
                        <label>País *</label>
                        <select name="pais" required>
                            <option value="PY" selected>Paraguay (PY)</option>
                            <option value="BR">Brasil (BR)</option>
                            <option value="AR">Argentina (AR)</option>
                            <option value="BO">Bolivia (BO)</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Teléfono</label><input type="text" name="telefono"></div>
                    <div class="form-group"><label>Línea de Crédito (USD)</label><input type="number" name="linea_credito_usd" value="0" step="0.01" min="0"></div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.5rem">
                    <button type="button" class="btn btn-ghost" onclick="cerrarModalCliente()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">💾 Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentStep = 1;
let selectedVehiculo = null;
let selectedCliente = null;
let pagoCount = 1;
let cuotaIdx = 0;
let currentRates = { PYG: 1, BRL: 1 };

// ══════════ STEP NAVIGATION ══════════
function goToStep(step) {
    if (step === 2 && !document.getElementById('vehiculo_id_input').value) {
        alert('Seleccioná un vehículo primero.'); return;
    }
    if (step === 3 && !document.getElementById('cliente_id_input').value) {
        alert('Seleccioná un cliente primero.'); return;
    }
    if (step === 4) buildResumen();

    document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
    document.getElementById('step' + step).classList.add('active');

    document.querySelectorAll('.step-item').forEach(s => {
        const n = parseInt(s.dataset.step);
        s.classList.remove('active', 'done');
        if (n === step) s.classList.add('active');
        else if (n < step) s.classList.add('done');
    });
    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ══════════ VEHICLE AND CLIENT ══════════
function seleccionarVehiculo(el) {
    document.querySelectorAll('#vehiculosGrid .vehicle-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('vehiculo_id_input').value = el.dataset.id;
    document.getElementById('btnStep1Next').disabled = false;
    selectedVehiculo = {
        id: el.dataset.id, marca: el.dataset.marca, modelo: el.dataset.modelo,
        chasis: el.dataset.chasis, año: el.dataset.año, costo: parseFloat(el.dataset.costo),
        precioSugerido: parseFloat(el.dataset.precioSugerido) || 0
    };
    const spanCosto = document.getElementById('valor_libro_span');
    spanCosto.dataset.costoRaw = selectedVehiculo.costo;
    spanCosto.textContent = '$ ' + formatNumber(selectedVehiculo.costo);
    document.getElementById('valor_libro_info').style.display = 'block';

    // Auto-populate the suggested retail price and default currency to USD
    if (selectedVehiculo.precioSugerido > 0) {
        const selectMoneda = document.querySelector('select[name="moneda_venta"]');
        if (selectMoneda) selectMoneda.value = 'USD';
        
        const inputMoneda = document.querySelector('input[name="precio_venta_moneda"]');
        if (inputMoneda) {
            inputMoneda.value = selectedVehiculo.precioSugerido.toFixed(2);
            // Trigger the input event to calculate USD equivalents, rent, and balances automatically
            inputMoneda.dispatchEvent(new Event('input', { bubbles: true }));
        }
    } else {
        // If there's no price, we just calculate rent cleanly
        calcRent();
    }
}

document.getElementById('buscarVehiculo').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#vehiculosGrid .vehicle-card').forEach(card => {
        const text = (card.dataset.marca + ' ' + card.dataset.modelo + ' ' + card.dataset.chasis).toLowerCase();
        card.style.display = text.includes(term) ? '' : 'none';
    });
});

function seleccionarCliente(el) {
    document.querySelectorAll('#clientesGrid .vehicle-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('cliente_id_input').value = el.dataset.id;
    document.getElementById('btnStep2Next').disabled = false;
    selectedCliente = {
        id: el.dataset.id, nombre: el.dataset.nombre, ruc: el.dataset.ruc,
        telefono: el.dataset.telefono, pais: el.dataset.pais
    };
}

document.getElementById('buscarCliente').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#clientesGrid .vehicle-card').forEach(card => {
        const text = (card.dataset.nombre + ' ' + card.dataset.ruc).toLowerCase();
        card.style.display = text.includes(term) ? '' : 'none';
    });
});

// ══════════ PAYMENT MODE ══════════
function setPaymentMode(mode) {
    document.getElementById('modalidad_pago').value = mode;
    document.querySelectorAll('.mode-toggle button').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    document.getElementById('texto_pagos').textContent = mode === 'CONTADO' ? 'Registrá los pagos recibidos al contado.' : 'Registrá las entregas iniciales (si aplica).';
    document.getElementById('label_total_pagos').textContent = mode === 'CONTADO' ? 'Total pagos registrados' : 'Total entregas iniciales';
    document.getElementById('label_saldo').textContent = mode === 'CONTADO' ? 'Saldo pendiente' : 'A Financiar';
    
    document.getElementById('seccion_plan_cuotas').style.display = mode === 'CONTADO' ? 'none' : 'block';
    calcularTotalPagos(); // Recalculate colors based on mode
}

// ══════════ COMMON PAYMENTS (PAGOS / ENTREGAS) ══════════
function agregarPago() {
    const idx = pagoCount++;
    const html = `
    <div class="payment-entry" id="pago_row_${idx}">
        <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end">
            <div class="form-group">
                <label>Tipo de pago</label>
                <select name="pagos[${idx}][tipo]" onchange="toggleCamposPago(${idx}, this.value)">
                    <option value="EFECTIVO">💵 Efectivo</option>
                    <option value="TRANSFERENCIA">🏦 Transferencia</option>
                    <option value="VEHICULO_CANJE">🚗 Vehículo parte de pago</option>
                    <option value="CHEQUE">📝 Cheque</option>
                </select>
            </div>
            <div class="form-group">
                <label>Monto USD</label>
                <input type="number" name="pagos[${idx}][monto_usd]" step="0.01" min="0" class="pago-monto" oninput="calcularTotalPagos()">
            </div>
            <div class="form-group" id="ref_container_${idx}" style="display:none">
                <label>Referencia / Banco</label>
                <input type="text" name="pagos[${idx}][referencia]" placeholder="Nro. transferencia...">
            </div>
            <div class="form-group" id="canje_container_${idx}" style="display:none">
                <label>Vehículo en Canje</label>
                <select name="pagos[${idx}][vehiculo_canje_id]">
                    <option value="">— Seleccionar —</option>
                    @foreach($vehiculos_canje ?? [] as $vc)
                    <option value="{{ $vc->id }}">{{ $vc->marca }} {{ $vc->modelo }} — {{ $vc->numero_chasis }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-ghost" onclick="eliminarPago(${idx})" style="color:var(--danger);padding:.4rem .6rem" title="Eliminar">🗑️</button>
        </div>
    </div>`;
    document.getElementById('pagos_container').insertAdjacentHTML('beforeend', html);
}

function eliminarPago(idx) {
    const row = document.getElementById('pago_row_' + idx);
    if (row) row.remove();
    calcularTotalPagos();
}

function toggleCamposPago(idx, tipo) {
    const ref = document.getElementById('ref_container_' + idx);
    const canje = document.getElementById('canje_container_' + idx);
    if (ref) ref.style.display = (tipo === 'TRANSFERENCIA' || tipo === 'CHEQUE') ? 'flex' : 'none';
    if (canje) canje.style.display = tipo === 'VEHICULO_CANJE' ? 'flex' : 'none';
}

function calcularTotalPagos() {
    let total = 0;
    document.querySelectorAll('.pago-monto').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('total_pagos_display').textContent = '$ ' + formatNumber(total);
    
    const precioBase = parseFloat(document.getElementById('precio_usd').value) || 0;
    const descuento = parseFloat(document.getElementById('descuento_usd').value) || 0;
    const precioFinal = Math.max(0, precioBase - descuento);
    
    const saldo = Math.max(0, precioFinal - total);
    
    document.getElementById('precio_final_display').textContent = '$ ' + formatNumber(precioFinal);
    document.getElementById('plan_precio_base').textContent = '$ ' + formatNumber(precioBase);
    document.getElementById('plan_descuento').textContent = '$ ' + formatNumber(descuento);
    document.getElementById('plan_precio_final').textContent = '$ ' + formatNumber(precioFinal);

    const saldoEl = document.getElementById('saldo_pendiente_display');
    saldoEl.textContent = '$ ' + formatNumber(saldo);
    
    const isContado = document.getElementById('modalidad_pago').value === 'CONTADO';
    if(isContado) {
        saldoEl.style.color = saldo <= 0 ? 'var(--success)' : 'var(--danger)';
    } else {
        saldoEl.style.color = 'var(--accent)'; // In cuotas, balance is what we finance
        document.getElementById('capital_usd_input').value = saldo.toFixed(2);
        document.getElementById('capital_total_usd_visual').value = saldo.toFixed(2);
        recalcularCuotasManuales();
    }
}

// ══════════ CUOTAS SECTION ══════════
function onTipoPlanChange() {
    const tipo = document.getElementById('tipo_plan').value;
    document.getElementById('auto-config').style.display = tipo === 'MANUAL' ? 'none' : 'block';
    document.getElementById('manual-config').style.display = tipo === 'MANUAL' ? 'block' : 'none';
}

function addCuotaManual(tipo) {
    cuotaIdx++;
    const container = document.getElementById('cuotas-container');
    const existingDates = container.querySelectorAll('input[name$="[fecha]"]');
    let nextDate = new Date();
    nextDate.setMonth(nextDate.getMonth() + 1);
    if (existingDates.length > 0) {
        const lastDate = new Date(existingDates[existingDates.length - 1].value);
        lastDate.setMonth(lastDate.getMonth() + 1);
        nextDate = lastDate;
    }
    const bgColor = tipo === 'REFUERZO' ? 'background:rgba(245,158,11,.08);border-radius:6px;padding:4px;' : '';

    const html = `
    <div class="cuota-row" id="cuota_${cuotaIdx}">
        <div style="${bgColor}"><strong style="font-size:.75rem;${tipo === 'REFUERZO' ? 'color:var(--warning)' : ''}">${cuotaIdx}</strong></div>
        <input type="date" name="cuotas_manual[${cuotaIdx}][fecha]" value="${nextDate.toISOString().split('T')[0]}">
        <select name="cuotas_manual[${cuotaIdx}][tipo]">
            <option value="REGULAR" ${tipo === 'REGULAR' ? 'selected' : ''}>Regular</option>
            <option value="REFUERZO" ${tipo === 'REFUERZO' ? 'selected' : ''}>Refuerzo</option>
        </select>
        <input type="number" name="cuotas_manual[${cuotaIdx}][monto]" step="0.01" min="0" value="0" class="cuota-monto" oninput="recalcularCuotasManuales()" style="${tipo === 'REFUERZO' ? 'border-color:var(--warning)' : ''}">
        <button type="button" class="btn btn-ghost" onclick="removeCuota(${cuotaIdx})" style="color:var(--danger);padding:.2rem">✕</button>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    recalcularCuotasManuales();
}

function removeCuota(idx) {
    document.getElementById('cuota_' + idx)?.remove();
    recalcularCuotasManuales();
}

function recalcularCuotasManuales() {
    if(document.getElementById('modalidad_pago').value !== 'CUOTAS') return;
    let totalCuotas = 0;
    document.querySelectorAll('.cuota-monto').forEach(el => totalCuotas += parseFloat(el.value) || 0);
    document.getElementById('total_cuotas_sum').textContent = '$ ' + formatNumber(totalCuotas);
    
    const saldo = parseFloat(document.getElementById('capital_usd_input').value) || 0;
    const diff = totalCuotas - saldo;
    const diffEl = document.getElementById('diferencia_cuotas');
    diffEl.textContent = (diff >= 0 ? '+' : '') + '$ ' + formatNumber(diff);
    diffEl.style.color = Math.abs(diff) < 0.01 ? 'var(--success)' : 'var(--danger)';
}


// ══════════ PRICING ══════════
function calcRent() {
    const costo = parseFloat(document.getElementById('valor_libro_span')?.dataset?.costoRaw) || 0;
    const precio = parseFloat(document.getElementById('precio_usd').value) || 0;
    const descuento = parseFloat(document.getElementById('descuento_usd').value) || 0;
    
    const precioFinal = Math.max(0, precio - descuento);
    const rent = precioFinal - costo;
    const span = document.getElementById('rent_span');
    span.textContent = '$ ' + formatNumber(rent);
    span.style.color = rent >= 0 ? 'var(--success)' : 'var(--danger)';
    calcularTotalPagos();
}

async function fetchRates() {
    try {
        const fecha = document.querySelector('input[name="fecha_venta"]').value || new Date().toISOString().split('T')[0];
        const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${fecha}`);
        currentRates = await res.json();
        calcularTotalVentaUsd();
    } catch(e) { console.error('Error fetching rates', e); }
}

function calcularTotalVentaUsd() {
    const precioMoneda = parseFloat(document.querySelector('input[name="precio_venta_moneda"]').value) || 0;
    const dctoMoneda = parseFloat(document.querySelector('input[name="descuento_moneda"]').value) || 0;
    const moneda = document.querySelector('select[name="moneda_venta"]').value;
    
    let precioUsd = precioMoneda;
    let dctoUsd = dctoMoneda;
    let tasa = 1;
    
    if (moneda === 'PYG') { 
        tasa = parseFloat(currentRates.PYG) || 1; 
        precioUsd = precioMoneda / tasa; 
        dctoUsd = dctoMoneda / tasa;
    }
    else if (moneda === 'BRL') { 
        tasa = parseFloat(currentRates.BRL) || 1; 
        precioUsd = precioMoneda / tasa;
        dctoUsd = dctoMoneda / tasa;
    }
    
    document.getElementById('precio_usd').value = precioUsd.toFixed(2);
    document.getElementById('descuento_usd').value = dctoUsd.toFixed(2);
    document.querySelector('input[name="tasa_cambio_venta"]').value = tasa.toFixed(2);
    calcRent();
}

// ══════════ RESUMEN ══════════
function buildResumen() {
    if (selectedVehiculo) {
        document.getElementById('resumen_vehiculo').textContent = selectedVehiculo.marca + ' ' + selectedVehiculo.modelo + ' — ' + selectedVehiculo.año;
        document.getElementById('resumen_chasis').textContent = 'Chasis: ' + selectedVehiculo.chasis;
    }
    if (selectedCliente) {
        document.getElementById('resumen_cliente').textContent = selectedCliente.nombre;
        document.getElementById('resumen_cliente_ruc').textContent = 'RUC: ' + (selectedCliente.ruc || 'N/A') + ' · ' + selectedCliente.pais;
    }
    const precioBase = parseFloat(document.getElementById('precio_usd').value) || 0;
    const descuento = parseFloat(document.getElementById('descuento_usd').value) || 0;
    const precioFinal = Math.max(0, precioBase - descuento);

    document.getElementById('resumen_precio').innerHTML = '$ ' + formatNumber(precioFinal) + ' USD' + 
        (descuento > 0 ? `<br><span style="font-size:.7rem;color:var(--danger);font-weight:normal">- $ ${formatNumber(descuento)} desc.</span>` : '');

    const mode = document.getElementById('modalidad_pago').value;
    document.getElementById('resumen_modalidad').textContent = mode === 'CONTADO' ? '💵 Contado' : ('📅 Cuotas (' + document.getElementById('tipo_plan').value + ')');

    const costo = parseFloat(document.getElementById('valor_libro_span')?.dataset?.costoRaw) || 0;
    const rent = precioFinal - costo;
    const rentEl = document.getElementById('resumen_rentabilidad');
    rentEl.textContent = '$ ' + formatNumber(rent);
    rentEl.style.color = rent >= 0 ? 'var(--success)' : 'var(--danger)';

    // Build payment summary
    let html = '';
    let totalPagos = 0;
    document.querySelectorAll('.payment-entry').forEach(row => {
        const tipo = row.querySelector('select')?.value || 'EFECTIVO';
        const monto = parseFloat(row.querySelector('.pago-monto')?.value) || 0;
        if (monto > 0) {
            totalPagos += monto;
            const labels = { EFECTIVO: '💵 Efectivo', TRANSFERENCIA: '🏦 Transferencia', VEHICULO_CANJE: '🚗 Vehículo canje', CHEQUE: '📝 Cheque' };
            html += `<div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--border);font-size:.85rem"><span>${mode==='CONTADO'?'Pago':'Entrega'}: ${labels[tipo] || tipo}</span><strong>$ ${formatNumber(monto)}</strong></div>`;
        }
    });

    if (mode === 'CUOTAS') {
        const financiar = Math.max(0, precioFinal - totalPagos);
        html += `<div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.85rem"><span>A financiar en cuotas</span><strong style="color:var(--accent)">$ ${formatNumber(financiar)}</strong></div>`;
    }
    document.getElementById('resumen_pagos_box').innerHTML = html ? `<div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem"><div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:.5rem">Detalle de ${mode==='CONTADO'?'pagos':'entregas y cuotas'}</div>${html}</div>` : '';
}

// ══════════ MODAL CLIENTE ══════════
function abrirModalCliente() { document.getElementById('modalCliente').style.display = 'flex'; }
function cerrarModalCliente() { document.getElementById('modalCliente').style.display = 'none'; document.getElementById('formModalCliente').reset(); }

document.getElementById('formModalCliente').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const res = await fetch("{{ route('clientes.store') }}", {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: new FormData(form)
        });
        const data = await res.json();
        if (data.success) {
            // Add card to grid
            const grid = document.getElementById('clientesGrid');
            const card = document.createElement('div');
            card.className = 'vehicle-card';
            card.dataset.id = data.cliente.id;
            card.dataset.nombre = data.cliente.razon_social;
            card.dataset.ruc = '';
            card.dataset.telefono = '';
            card.dataset.pais = '';
            card.onclick = function() { seleccionarCliente(this); };
            card.innerHTML = `<div style="font-weight:600;margin-bottom:.25rem">${data.cliente.razon_social}</div><div style="font-size:.78rem;color:var(--text-muted)">Recién creado</div>`;
            grid.prepend(card);
            seleccionarCliente(card);
            cerrarModalCliente();
        } else { alert('Error al guardar.'); }
    } catch(err) { console.error(err); alert('Error de conexión.'); }
    finally { btn.disabled = false; btn.textContent = '💾 Guardar Cliente'; }
});

// Init
fetchRates();
document.querySelector('input[name="fecha_venta"]').addEventListener('change', fetchRates);
onTipoPlanChange();
</script>
@endpush
@endsection
