@extends('layouts.app')
@section('title', 'Editar Venta')
@section('page-title', 'Editar Venta #' . $venta->numero_venta)

@section('content')
@if($errors->any())
<div class="flash-error">{{ $errors->first() }}</div>
@endif

<div style="margin-bottom:1rem"><a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver al Detalle</a></div>

<form method="POST" action="{{ route('ventas.update', $venta->id) }}" id="ventaForm">
    @csrf
    @method('PUT')

    {{-- DATOS GENERALES --}}
    <div class="erp-card mb-6">
        <div class="erp-card-header"><h2>Datos de la Venta</h2></div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="form-group">
                    <label class="form-label">Fecha de venta <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_venta" class="form-input" value="{{ $venta->fecha_venta instanceof \Carbon\Carbon ? $venta->fecha_venta->format('Y-m-d') : $venta->fecha_venta }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cliente <span class="text-red-500">*</span></label>
                    <select name="cliente_id" class="form-input" required>
                        <option value="">Seleccionar...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ $venta->cliente_id == $c->id ? 'selected' : '' }}>{{ $c->razon_social }} ({{ $c->ruc ?: 'S/RUC' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Moneda <span class="text-red-500">*</span></label>
                    <select name="moneda_venta" class="form-input" required onchange="calcularTotalVentaUsd()">
                        @foreach(['USD', 'PYG', 'BRL'] as $m)
                            <option value="{{ $m }}" {{ $venta->moneda_venta == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Modalidad <span class="text-red-500">*</span></label>
                    <select name="modalidad_pago" id="modalidad_pago" class="form-input" required onchange="togglePlanSection()">
                        <option value="CONTADO" {{ $venta->modalidad_pago == 'CONTADO' ? 'selected' : '' }}>Contado</option>
                        <option value="CUOTAS" {{ $venta->modalidad_pago == 'CUOTAS' ? 'selected' : '' }}>Cuotas</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-input" required>
                        @foreach(['PRESUPUESTO', 'RESERVADO', 'EN_PROCESO', 'COMPLETADO'] as $e)
                            <option value="{{ $e }}" {{ $venta->estado == $e ? 'selected' : '' }}>{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- PRECIOS --}}
    <div class="erp-card mb-6">
        <div class="erp-card-header"><h2>Precios y Descuentos</h2></div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="form-group">
                    <label class="form-label">Precio en moneda <span class="text-red-500">*</span></label>
                    <input type="number" name="precio_venta_moneda" class="form-input" value="{{ $venta->precio_venta_moneda }}" step="0.01" min="0" required id="precio_moneda" oninput="calcularTotalVentaUsd()">
                </div>
                <div class="form-group">
                    <label class="form-label">Precio en USD <span class="text-red-500">*</span></label>
                    <input type="number" name="precio_venta_usd" class="form-input" value="{{ $venta->precio_venta_usd }}" step="0.01" min="0" required id="precio_usd" oninput="calcRent()">
                </div>
                <div class="form-group">
                    <label class="form-label">Descuento (moneda)</label>
                    <input type="number" name="descuento_moneda" class="form-input" value="{{ $venta->descuento_moneda }}" step="0.01" min="0" id="descuento_moneda" oninput="calcularTotalVentaUsd()">
                </div>
                <div class="form-group">
                    <label class="form-label">Descuento (USD)</label>
                    <input type="number" name="descuento_usd" class="form-input" value="{{ $venta->descuento_usd }}" step="0.01" min="0" id="descuento_usd" oninput="calcRent()">
                </div>
            </div>
            <input type="hidden" name="tasa_cambio_venta" value="{{ $venta->tasa_cambio_venta }}">
            <div class="bg-indigo-50/50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800/50 rounded-xl p-5 mt-6 flex justify-between items-center">
                <span class="text-sm font-bold text-indigo-700 dark:text-indigo-400 uppercase">Precio Final (USD)</span>
                <strong class="text-2xl text-indigo-700 dark:text-indigo-400 font-extrabold" id="precio_final_display">$ {{ number_format(max(0, $venta->precio_venta_usd - $venta->descuento_usd), 2, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    {{-- ITEMS --}}
    <div class="erp-card mb-6">
        <div class="erp-card-header flex justify-between items-center">
            <h2>Items de la Venta</h2>
            <button type="button" onclick="addItemRow()" class="btn btn-ghost btn-sm text-primary">+ Añadir Item</button>
        </div>
        <div class="p-6">
            <table class="erp-table text-xs" id="itemsTable">
                <thead>
                    <tr class="bg-surface2/50">
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>ID Ref.</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-right">Precio Unit. USD</th>
                        <th class="text-right">Costo USD</th>
                        <th class="text-right">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    @foreach($venta->items as $idx => $item)
                    <tr class="item-row border-b border-white/5">
                        <td><input type="text" name="items[{{ $idx }}][descripcion]" value="{{ $item->descripcion }}" class="form-input text-xs" required></td>
                        <td>
                            <select name="items[{{ $idx }}][itemable_type]" class="form-input text-xs">
                                <option value="App\Models\Vehicle" {{ str_contains($item->itemable_type, 'Vehicle') ? 'selected' : '' }}>Vehículo</option>
                                <option value="App\Models\StockRepuesto" {{ str_contains($item->itemable_type, 'Repuesto') ? 'selected' : '' }}>Repuesto</option>
                            </select>
                        </td>
                        <td><input type="number" name="items[{{ $idx }}][itemable_id]" value="{{ $item->itemable_id }}" class="form-input text-xs w-20" required></td>
                        <td><input type="number" name="items[{{ $idx }}][cantidad]" value="{{ $item->cantidad }}" step="0.01" min="0.01" class="form-input text-xs text-center w-20 qty-input" required oninput="calcRowSubtotal(this)"></td>
                        <td><input type="number" name="items[{{ $idx }}][precio_unitario_usd]" value="{{ $item->precio_unitario_usd }}" step="0.01" min="0" class="form-input text-xs text-right w-28 price-input" oninput="calcRowSubtotal(this)"></td>
                        <td><input type="number" name="items[{{ $idx }}][costo_snapshot_usd]" value="{{ $item->costo_snapshot_usd }}" step="0.01" min="0" class="form-input text-xs text-right w-24"></td>
                        <td class="text-right font-mono font-bold text-accent subtotal-cell">{{ number_format($item->subtotal_usd, 2, ',', '.') }}</td>
                        <td><button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:bg-red-500/10 p-1 rounded">✕</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div id="emptyItemsMsg" class="text-center text-muted-foreground italic py-8 {{ count($venta->items) > 0 ? 'hidden' : '' }}">No hay items. Añada al menos uno.</div>
        </div>
    </div>

    {{-- PAGOS --}}
    <div class="erp-card mb-6">
        <div class="erp-card-header flex justify-between items-center">
            <h2>Pagos / Entregas</h2>
            <button type="button" onclick="agregarPago()" class="btn btn-ghost btn-sm text-primary">+ Añadir Pago</button>
        </div>
        <div class="p-6">
            <div id="pagos_container">
                @php $pagoIdx = 0; @endphp
                @foreach($venta->pagos as $pago)
                    @if($pago->tipo_pago !== 'PLAN_CUOTAS')
                    <div class="payment-entry grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50 mb-3" id="pago_row_{{ $pagoIdx }}">
                        <div class="form-group">
                            <label class="form-label text-xs">Tipo</label>
                            <select name="pagos[{{ $pagoIdx }}][tipo]" class="form-input" onchange="togglePagoFields({{ $pagoIdx }}, this.value)">
                                <option value="EFECTIVO" {{ $pago->tipo_pago == 'EFECTIVO' ? 'selected' : '' }}>Efectivo</option>
                                <option value="TRANSFERENCIA" {{ $pago->tipo_pago == 'TRANSFERENCIA' ? 'selected' : '' }}>Transferencia</option>
                                <option value="CHEQUE" {{ $pago->tipo_pago == 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                                <option value="TARJETA" {{ $pago->tipo_pago == 'TARJETA' ? 'selected' : '' }}>Tarjeta</option>
                                <option value="VEHICULO_CANJE" {{ $pago->tipo_pago == 'VEHICULO_CANJE' ? 'selected' : '' }}>Vehículo Canje</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label text-xs">Monto USD</label>
                            <input type="number" name="pagos[{{ $pagoIdx }}][monto_usd]" value="{{ $pago->monto_usd }}" step="0.01" min="0" class="form-input pago-monto" oninput="calcularTotalPagos()">
                        </div>
                        <div class="form-group" id="ref_container_{{ $pagoIdx }}" style="display:{{ in_array($pago->tipo_pago, ['TRANSFERENCIA', 'CHEQUE']) ? 'block' : 'none' }}">
                            <label class="form-label text-xs">Referencia</label>
                            <input type="text" name="pagos[{{ $pagoIdx }}][referencia]" value="{{ $pago->referencia_bancaria }}" class="form-input" placeholder="Nro. referencia...">
                        </div>
                        <div class="form-group" id="canje_container_{{ $pagoIdx }}" style="display:{{ $pago->tipo_pago == 'VEHICULO_CANJE' ? 'block' : 'none' }}">
                            <label class="form-label text-xs">Vehículo Canje</label>
                            <select name="pagos[{{ $pagoIdx }}][vehiculo_canje_id]" class="form-input">
                                <option value="">— Seleccionar —</option>
                                @foreach($vehiculos_canje ?? [] as $vc)
                                    <option value="{{ $vc->id }}" {{ $pago->vehiculo_canje_id == $vc->id ? 'selected' : '' }}>{{ $vc->marca }} {{ $vc->modelo }} — {{ $vc->numero_chasis }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" onclick="document.getElementById('pago_row_{{ $pagoIdx }}').remove(); calcularTotalPagos();" class="text-red-500 text-xs">✕ Eliminar</button>
                    </div>
                    @php $pagoIdx++; @endphp
                    @endif
                @endforeach
            </div>
            <div class="bg-slate-100 dark:bg-slate-800/80 rounded-xl p-5 flex justify-between items-center mt-4">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Total Pagos</div>
                    @php
                        $totalPagos = $venta->pagos->where('tipo_pago', '!=', 'PLAN_CUOTAS')->sum('monto_usd');
                    @endphp
                    <div class="text-xl font-extrabold" id="total_pagos_display">$ {{ number_format($totalPagos, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- PLAN DE CUOTAS (si aplica) --}}
    <div id="seccion_plan_cuotas" class="erp-card mb-6" style="display:{{ $venta->modalidad_pago == 'CUOTAS' ? 'block' : 'none' }}">
        <div class="erp-card-header"><h2>Plan de Cuotas</h2></div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div class="form-group">
                    <label class="form-label">Tipo de plan</label>
                    <select name="tipo_plan" id="tipo_plan" class="form-input" onchange="togglePlanConfig()">
                        <option value="FRANCESA" {{ ($plan->tipo_plan ?? 'MANUAL') == 'FRANCESA' ? 'selected' : '' }}>Cuota Fija</option>
                        <option value="MANUAL" {{ ($plan->tipo_plan ?? 'MANUAL') == 'MANUAL' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Capital a financiar (USD)</label>
                    <input type="number" name="capital_total_usd" id="capital_usd_input" value="{{ $plan->capital_total_usd ?? 0 }}" step="0.01" min="0" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Número de cuotas</label>
                    <input type="number" name="numero_cuotas" id="numero_cuotas" value="{{ $plan->numero_cuotas ?? 12 }}" min="1" max="120" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Tasa interés mensual (%)</label>
                    <input type="number" name="tasa_interes_mensual" value="{{ $plan->tasa_interes_mensual ?? 0 }}" step="0.01" min="0" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha primera cuota</label>
                    <input type="date" name="fecha_primera_cuota" value="{{ $plan->fecha_primera_cuota ?? \Carbon\Carbon::now()->addMonth()->format('Y-m-d') }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Refuerzo cada N meses</label>
                    <input type="number" name="refuerzo_cada" value="0" min="0" max="12" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Monto refuerzo (USD)</label>
                    <input type="number" name="refuerzo_monto" value="0" step="0.01" min="0" class="form-input">
                </div>
            </div>

            {{-- Cuotas manuales --}}
            <div id="manual-cuotas-section" style="display:{{ ($plan->tipo_plan ?? 'MANUAL') == 'MANUAL' ? 'block' : 'none' }}">
                <h4 class="text-sm font-bold mb-3">Cuotas Manuales</h4>
                <div id="cuotas-container" class="flex flex-col gap-2">
                    @if(isset($cuotas) && $cuotas->count() > 0)
                        @foreach($cuotas as $ci => $c)
                        <div class="cuota-row grid grid-cols-[30px_1fr_1fr_1fr_auto] gap-2 items-center px-2 p-2 rounded-lg border border-slate-100 dark:border-slate-700" id="cuota_{{ $ci }}">
                            <strong class="text-xs">{{ $ci + 1 }}</strong>
                            <input type="date" name="cuotas_manual[{{ $ci }}][fecha]" value="{{ $c->fecha_vencimiento }}" class="form-input text-xs">
                            <select name="cuotas_manual[{{ $ci }}][tipo]" class="form-input text-xs">
                                <option value="REGULAR" {{ ($c->tipo_plan ?? 'REGULAR') == 'REGULAR' ? 'selected' : '' }}>Regular</option>
                                <option value="REFUERZO" {{ ($c->tipo_plan ?? '') == 'REFUERZO' ? 'selected' : '' }}>Refuerzo</option>
                            </select>
                            <input type="number" name="cuotas_manual[{{ $ci }}][monto]" value="{{ $c->capital }}" step="0.01" min="0" class="form-input text-xs cuota-monto" oninput="recalcularCuotasManuales()">
                            <button type="button" onclick="document.getElementById('cuota_{{ $ci }}').remove(); recalcularCuotasManuales();" class="text-red-500 text-xs">✕</button>
                        </div>
                        @endforeach
                    @endif
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="addCuotaManual('REGULAR')">+ Regular</button>
                    <button type="button" class="btn btn-ghost btn-sm text-amber-500" onclick="addCuotaManual('REFUERZO')">+ Refuerzo</button>
                </div>
                <div class="mt-4 text-sm">
                    Total cuotas: <strong id="total_cuotas_sum">$ 0.00</strong>
                    &nbsp;|&nbsp; Diferencia: <strong id="diferencia_cuotas">$ 0.00</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- OBSERVACIONES Y SUBMIT --}}
    <div class="erp-card mb-6">
        <div class="p-6">
            <div class="form-group mb-4">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" rows="3" class="form-input">{{ $venta->observaciones }}</textarea>
            </div>
            <div class="flex justify-between items-center">
                <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary px-8 py-3 text-sm font-bold">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    let pagoCount = {{ $pagoIdx }};
    let cuotaIdx = {{ isset($cuotas) ? $cuotas->count() : 0 }};
    let currentRates = { PYG: 1, BRL: 1 };

    function togglePlanSection() {
        const mode = document.getElementById('modalidad_pago').value;
        document.getElementById('seccion_plan_cuotas').style.display = mode === 'CUOTAS' ? 'block' : 'none';
    }

    function togglePlanConfig() {
        const tipo = document.getElementById('tipo_plan').value;
        document.getElementById('manual-cuotas-section').style.display = tipo === 'MANUAL' ? 'block' : 'none';
    }

    function addItemRow() {
        const tbody = document.getElementById('itemsBody');
        const idx = tbody.querySelectorAll('.item-row').length;
        const html = `<tr class="item-row border-b border-white/5">
            <td><input type="text" name="items[${idx}][descripcion]" class="form-input text-xs" required></td>
            <td><select name="items[${idx}][itemable_type]" class="form-input text-xs">
                <option value="App\\Models\\Vehicle">Vehículo</option>
                <option value="App\\Models\\StockRepuesto">Repuesto</option>
            </select></td>
            <td><input type="number" name="items[${idx}][itemable_id]" class="form-input text-xs w-20" required></td>
            <td><input type="number" name="items[${idx}][cantidad]" value="1" step="0.01" min="0.01" class="form-input text-xs text-center w-20 qty-input" required oninput="calcRowSubtotal(this)"></td>
            <td><input type="number" name="items[${idx}][precio_unitario_usd]" value="0" step="0.01" min="0" class="form-input text-xs text-right w-28 price-input" oninput="calcRowSubtotal(this)"></td>
            <td><input type="number" name="items[${idx}][costo_snapshot_usd]" value="0" step="0.01" min="0" class="form-input text-xs text-right w-24"></td>
            <td class="text-right font-mono font-bold text-accent subtotal-cell">0.00</td>
            <td><button type="button" onclick="this.closest('tr').remove(); toggleEmptyMsg()" class="text-red-500 hover:bg-red-500/10 p-1 rounded">✕</button></td>
        </tr>`;
        tbody.insertAdjacentHTML('beforeend', html);
        document.getElementById('emptyItemsMsg').classList.add('hidden');
    }

    function calcRowSubtotal(input) {
        const row = input.closest('tr');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        row.querySelector('.subtotal-cell').textContent = (qty * price).toLocaleString('es-PY', {minimumFractionDigits: 2});
    }

    function toggleEmptyMsg() {
        const count = document.querySelectorAll('#itemsBody .item-row').length;
        document.getElementById('emptyItemsMsg').classList.toggle('hidden', count > 0);
    }

    function agregarPago() {
        const idx = pagoCount++;
        const html = `<div class="payment-entry grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50 mb-3" id="pago_row_${idx}">
            <div class="form-group">
                <label class="form-label text-xs">Tipo</label>
                <select name="pagos[${idx}][tipo]" class="form-input" onchange="togglePagoFields(${idx}, this.value)">
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="CHEQUE">Cheque</option>
                    <option value="TARJETA">Tarjeta</option>
                    <option value="VEHICULO_CANJE">Vehículo Canje</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label text-xs">Monto USD</label>
                <input type="number" name="pagos[${idx}][monto_usd]" step="0.01" min="0" class="form-input pago-monto" oninput="calcularTotalPagos()">
            </div>
            <div class="form-group hidden" id="ref_container_${idx}">
                <label class="form-label text-xs">Referencia</label>
                <input type="text" name="pagos[${idx}][referencia]" class="form-input" placeholder="Nro. referencia...">
            </div>
            <div class="form-group hidden" id="canje_container_${idx}">
                <label class="form-label text-xs">Vehículo Canje</label>
                <select name="pagos[${idx}][vehiculo_canje_id]" class="form-input">
                    <option value="">— Seleccionar —</option>
                    @foreach($vehiculos_canje ?? [] as $vc)
                        <option value="{{ $vc->id }}">{{ $vc->marca }} {{ $vc->modelo }} — {{ $vc->numero_chasis }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" onclick="document.getElementById('pago_row_${idx}').remove(); calcularTotalPagos();" class="text-red-500 text-xs">✕ Eliminar</button>
        </div>`;
        document.getElementById('pagos_container').insertAdjacentHTML('beforeend', html);
    }

    function togglePagoFields(idx, tipo) {
        const ref = document.getElementById('ref_container_' + idx);
        const canje = document.getElementById('canje_container_' + idx);
        if (ref) ref.classList.toggle('hidden', !(tipo === 'TRANSFERENCIA' || tipo === 'CHEQUE'));
        if (canje) canje.classList.toggle('hidden', tipo !== 'VEHICULO_CANJE');
    }

    function calcularTotalPagos() {
        let total = 0;
        document.querySelectorAll('.pago-monto').forEach(input => { total += parseFloat(input.value) || 0; });
        document.getElementById('total_pagos_display').textContent = '$ ' + total.toLocaleString('es-PY', {minimumFractionDigits: 2});
    }

    function addCuotaManual(tipo) {
        cuotaIdx++;
        const container = document.getElementById('cuotas-container');
        const html = `<div class="cuota-row grid grid-cols-[30px_1fr_1fr_1fr_auto] gap-2 items-center px-2 p-2 rounded-lg border border-slate-100 dark:border-slate-700" id="cuota_${cuotaIdx}">
            <strong class="text-xs">${cuotaIdx}</strong>
            <input type="date" name="cuotas_manual[${cuotaIdx}][fecha]" class="form-input text-xs">
            <select name="cuotas_manual[${cuotaIdx}][tipo]" class="form-input text-xs">
                <option value="REGULAR" ${tipo === 'REGULAR' ? 'selected' : ''}>Regular</option>
                <option value="REFUERZO" ${tipo === 'REFUERZO' ? 'selected' : ''}>Refuerzo</option>
            </select>
            <input type="number" name="cuotas_manual[${cuotaIdx}][monto]" value="0" step="0.01" min="0" class="form-input text-xs cuota-monto" oninput="recalcularCuotasManuales()">
            <button type="button" onclick="document.getElementById('cuota_${cuotaIdx}').remove(); recalcularCuotasManuales();" class="text-red-500 text-xs">✕</button>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function recalcularCuotasManuales() {
        let total = 0;
        document.querySelectorAll('.cuota-monto').forEach(el => total += parseFloat(el.value) || 0);
        document.getElementById('total_cuotas_sum').textContent = '$ ' + total.toLocaleString('es-PY', {minimumFractionDigits: 2});
        const capital = parseFloat(document.getElementById('capital_usd_input').value) || 0;
        const diff = total - capital;
        const diffEl = document.getElementById('diferencia_cuotas');
        diffEl.textContent = (diff >= 0 ? '+' : '') + '$ ' + diff.toLocaleString('es-PY', {minimumFractionDigits: 2});
        diffEl.style.color = Math.abs(diff) < 0.01 ? 'var(--success)' : 'var(--danger)';
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
        const precioMoneda = parseFloat(document.getElementById('precio_moneda').value) || 0;
        const dctoMoneda = parseFloat(document.getElementById('descuento_moneda').value) || 0;
        const moneda = document.querySelector('select[name="moneda_venta"]').value;
        let precioUsd = precioMoneda;
        let dctoUsd = dctoMoneda;
        let tasa = 1;
        if (moneda === 'PYG') { tasa = parseFloat(currentRates.PYG) || 1; precioUsd = precioMoneda / tasa; dctoUsd = dctoMoneda / tasa; }
        else if (moneda === 'BRL') { tasa = parseFloat(currentRates.BRL) || 1; precioUsd = precioMoneda / tasa; dctoUsd = dctoMoneda / tasa; }
        document.getElementById('precio_usd').value = precioUsd.toFixed(2);
        document.getElementById('descuento_usd').value = dctoUsd.toFixed(2);
        document.querySelector('input[name="tasa_cambio_venta"]').value = tasa.toFixed(2);
        calcRent();
    }

    function calcRent() {
        const precio = parseFloat(document.getElementById('precio_usd').value) || 0;
        const descuento = parseFloat(document.getElementById('descuento_usd').value) || 0;
        const final_ = Math.max(0, precio - descuento);
        document.getElementById('precio_final_display').textContent = '$ ' + final_.toLocaleString('es-PY', {minimumFractionDigits: 2});
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchRates();
        setTimeout(() => recalcularCuotasManuales(), 200);
    });
</script>
@endpush
@endsection
