@extends('layouts.app')
@section('title', 'Crear Plan de Pagos')
@section('page-title', 'Plan de Pagos Personalizado')

@section('content')
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}
    </div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver a la
            venta</a></div>

    <div
        style="background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:1rem;margin-bottom:1.5rem;font-size:.875rem">
        📋 Venta <strong>{{ $venta->numero_venta }}</strong> &nbsp;|&nbsp;
        Vehículo: <strong>{{ $venta->vehiculo->marca ?? '' }} {{ $venta->vehiculo->modelo ?? '' }}</strong> &nbsp;|&nbsp;
        Cliente: <strong>{{ $venta->cliente->razon_social ?? '' }}</strong> &nbsp;|&nbsp;
        Precio Total: <strong style="color:var(--accent)">$ {{ number_format($venta->precio_venta_usd, 2, ',', '.') }}
            USD</strong>
    </div>

    <form method="POST" action="{{ route('planes_cuotas.store', $venta->id) }}" id="planForm"
        data-confirm="Confirmar plan de pagos">
        @csrf

        {{-- ═══════════════════════════════════════════════════════
        SECCIÓN 1: ENTREGAS INICIALES (Down Payments)
        ═══════════════════════════════════════════════════════ --}}
        <div class="erp-card" style="margin-bottom:1.25rem">
            <div class="section-title" style="margin-top:0;padding:1rem 1rem 0.5rem">💰 Entregas Iniciales (Down Payments)
            </div>
            <div style="padding:0 1rem 1rem">
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem">
                    Registra los pagos que el cliente realiza al momento de la compra: efectivo, transferencia, cheque o
                    vehículo en parte de pago.
                </p>

                <div id="entregas-container">
                    {{-- Las filas de entrega se agregan dinámicamente --}}
                </div>
                <button type="button" class="btn-add" onclick="addEntrega()">+ Agregar entrega</button>

                <div class="totals-bar" style="margin-top:1rem">
                    <span>Total Entregas: <strong id="total_entregas" style="color:var(--success)">$ 0.00
                            USD</strong></span>
                    <span>Saldo a Financiar: <strong id="saldo_financiar" style="color:var(--accent)">$
                            {{ number_format($venta->precio_venta_usd, 2, ',', '.') }} USD</strong></span>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
        SECCIÓN 2: CONFIGURACIÓN DEL PLAN DE CUOTAS
        ═══════════════════════════════════════════════════════ --}}
        <div class="erp-card" style="margin-bottom:1.25rem">
            <div class="section-title" style="margin-top:0;padding:1rem 1rem 0.5rem">📅 Plan de Cuotas</div>
            <div style="padding:0 1rem 1rem">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tipo de plan *</label>
                        <select name="tipo_plan" id="tipo_plan" required onchange="onTipoPlanChange()">
                            <option value="FRANCESA">Francesa (cuota fija)</option>
                            <option value="ALEMANA">Alemana (capital fijo)</option>
                            <option value="MANUAL" selected>Manual / Personalizado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Moneda *</label>
                        <select name="moneda" required>
                            @foreach(['USD', 'PYG', 'BRL'] as $m)
                                <option value="{{ $m }}" {{ old('moneda', 'USD') == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Capital a financiar (USD) *</label>
                        <input type="number" name="capital_total_usd" id="capital_usd_input"
                            value="{{ old('capital_total_usd', $venta->precio_venta_usd) }}" step="0.01" min="0" required
                            readonly style="background:var(--surface1);cursor:not-allowed">
                    </div>
                    <div class="form-group">
                        <label>Capital a financiar (moneda) *</label>
                        <input type="number" name="capital_total" id="capital_total_input"
                            value="{{ old('capital_total', $venta->precio_venta_moneda) }}" step="0.01" min="0" required>
                    </div>
                </div>

                {{-- Campos para auto-generación (Francesa / Alemana) --}}
                <div id="auto-config" style="display:none; margin-top:1rem">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Número de cuotas *</label>
                            <input type="number" name="numero_cuotas" id="numero_cuotas"
                                value="{{ old('numero_cuotas', 12) }}" min="1" max="120">
                        </div>
                        <div class="form-group">
                            <label>Tasa de interés mensual (%)</label>
                            <input type="number" name="tasa_interes_mensual" value="{{ old('tasa_interes_mensual', 0) }}"
                                step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Fecha primera cuota *</label>
                            <input type="date" name="fecha_primera_cuota"
                                value="{{ old('fecha_primera_cuota', \Carbon\Carbon::now()->addMonth()->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label>Refuerzo cada N meses (opcional)</label>
                            <input type="number" name="refuerzo_cada" id="refuerzo_cada" min="0" max="12" value="0"
                                placeholder="0 = sin refuerzo">
                        </div>
                        <div class="form-group">
                            <label>Monto de cada refuerzo (USD)</label>
                            <input type="number" name="refuerzo_monto" id="refuerzo_monto" step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>

                {{-- Grilla manual de cuotas --}}
                <div id="manual-config" style="margin-top:1rem">
                    <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.75rem">
                        Agrega filas de cuotas manualmente. Puedes mezclar cuotas regulares y refuerzos con montos y fechas
                        distintos.
                    </p>
                    <div
                        style="font-size:.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:.5rem">
                        <div class="cuota-row" style="margin-bottom:0">
                            <span>#</span><span>Fecha Venc.</span><span>Tipo</span><span>Monto (USD)</span><span></span>
                        </div>
                    </div>
                    <div id="cuotas-container">
                        {{-- Filas dinámicas --}}
                    </div>
                    <div style="display:flex; gap:.5rem; margin-top:.5rem">
                        <button type="button" class="btn-add" onclick="addCuotaManual('REGULAR')">+ Cuota Regular</button>
                        <button type="button" class="btn-add" style="background:var(--warning);color:#000"
                            onclick="addCuotaManual('REFUERZO')">⚡ Cuota Refuerzo</button>
                    </div>

                    <div class="totals-bar" style="margin-top:1rem">
                        <span>Total Cuotas: <strong id="total_cuotas_sum" style="color:var(--accent)">$ 0.00
                                USD</strong></span>
                        <span>Diferencia vs Saldo: <strong id="diferencia_cuotas" style="color:var(--success)">$
                                0.00</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem">
            <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">📅 Guardar Plan de Pagos</button>
        </div>
    </form>

    @push('scripts')
        <script>
            const PRECIO_TOTAL_USD = {{ $venta->precio_venta_usd }};
            let entregaIdx = 0;
            let cuotaIdx = 0;

            // ── ENTREGAS ─────────────────────────────────
            function addEntrega() {
                entregaIdx++;
                const container = document.getElementById('entregas-container');
                const div = document.createElement('div');
                div.className = 'entrega-row';
                div.id = 'entrega-' + entregaIdx;
                div.innerHTML = `
                                        <div class="form-group">
                                            <label>Tipo pago</label>
                                            <select name="entregas[${entregaIdx}][tipo]">
                                                <option value="EFECTIVO">Efectivo</option>
                                                <option value="TRANSFERENCIA">Transferencia</option>
                                                <option value="CHEQUE">Cheque</option>
                                                <option value="VEHICULO_CANJE">Vehículo Canje</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Monto (USD)</label>
                                            <input type="number" name="entregas[${entregaIdx}][monto_usd]" step="0.01" min="0" value="0" oninput="recalcularTotales()">
                                        </div>
                                        <div class="form-group">
                                            <label>Referencia / Obs</label>
                                            <input type="text" name="entregas[${entregaIdx}][referencia]" placeholder="Nro. cheque, datos canje...">
                                        </div>
                                        <div class="form-group">
                                            <label>Fecha</label>
                                            <input type="date" name="entregas[${entregaIdx}][fecha]" value="${new Date().toISOString().split('T')[0]}">
                                        </div>
                                        <button type="button" class="btn-remove" onclick="removeEntrega(${entregaIdx})">✕</button>
                                    `;
                container.appendChild(div);
                recalcularTotales();
            }

            function removeEntrega(idx) {
                document.getElementById('entrega-' + idx)?.remove();
                recalcularTotales();
            }

            // ── CUOTAS MANUALES ──────────────────────────
            function addCuotaManual(tipo) {
                cuotaIdx++;
                const container = document.getElementById('cuotas-container');
                const div = document.createElement('div');
                div.className = 'cuota-row';
                div.id = 'cuota-' + cuotaIdx;

                // Calculate proxima fecha
                const existingDates = container.querySelectorAll('input[name$="[fecha]"]');
                let nextDate = new Date();
                nextDate.setMonth(nextDate.getMonth() + 1);
                if (existingDates.length > 0) {
                    const lastDate = new Date(existingDates[existingDates.length - 1].value);
                    lastDate.setMonth(lastDate.getMonth() + 1);
                    nextDate = lastDate;
                }

                const bgColor = tipo === 'REFUERZO' ? 'background:rgba(255,193,7,.08);border-radius:8px;padding:4px;' : '';

                div.innerHTML = `
                                        <div style="${bgColor}"><strong style="font-size:.75rem;${tipo === 'REFUERZO' ? 'color:var(--warning)' : ''}">${cuotaIdx}</strong></div>
                                        <input type="date" name="cuotas_manual[${cuotaIdx}][fecha]" value="${nextDate.toISOString().split('T')[0]}">
                                        <select name="cuotas_manual[${cuotaIdx}][tipo]">
                                            <option value="REGULAR" ${tipo === 'REGULAR' ? 'selected' : ''}>Regular</option>
                                            <option value="REFUERZO" ${tipo === 'REFUERZO' ? 'selected' : ''}>Refuerzo</option>
                                        </select>
                                        <input type="number" name="cuotas_manual[${cuotaIdx}][monto]" step="0.01" min="0" value="0" oninput="recalcularTotales()" style="${tipo === 'REFUERZO' ? 'border-color:var(--warning)' : ''}">
                                        <button type="button" class="btn-remove" onclick="removeCuota(${cuotaIdx})">✕</button>
                                    `;
                container.appendChild(div);
                recalcularTotales();
            }

            function removeCuota(idx) {
                document.getElementById('cuota-' + idx)?.remove();
                recalcularTotales();
            }

            // ── RECALCULAR TOTALES ───────────────────────
            function recalcularTotales() {
                // Total entregas
                let totalEntregas = 0;
                document.querySelectorAll('#entregas-container input[name$="[monto_usd]"]').forEach(el => {
                    totalEntregas += parseFloat(el.value) || 0;
                });
                document.getElementById('total_entregas').textContent = '$ ' + formatNumber(totalEntregas) + ' USD';

                // Saldo a financiar
                const saldo = Math.max(0, PRECIO_TOTAL_USD - totalEntregas);
                document.getElementById('saldo_financiar').textContent = '$ ' + formatNumber(saldo) + ' USD';
                document.getElementById('capital_usd_input').value = saldo.toFixed(2);

                // Total cuotas manuales
                let totalCuotas = 0;
                document.querySelectorAll('#cuotas-container input[name$="[monto]"]').forEach(el => {
                    totalCuotas += parseFloat(el.value) || 0;
                });
                document.getElementById('total_cuotas_sum').textContent = '$ ' + formatNumber(totalCuotas) + ' USD';

                // Diferencia
                const diff = totalCuotas - saldo;
                const diffEl = document.getElementById('diferencia_cuotas');
                diffEl.textContent = (diff >= 0 ? '+' : '') + '$ ' + formatNumber(diff);
                diffEl.style.color = Math.abs(diff) < 0.01 ? 'var(--success)' : 'var(--danger)';
            }

            // ── TOGGLE PLAN TYPE ─────────────────────────
            function onTipoPlanChange() {
                const tipo = document.getElementById('tipo_plan').value;
                document.getElementById('auto-config').style.display = tipo === 'MANUAL' ? 'none' : 'block';
                document.getElementById('manual-config').style.display = tipo === 'MANUAL' ? 'block' : 'none';
            }
            onTipoPlanChange();

            // Initial: add one empty entrega and one cuota so user sees the layout
            // addEntrega(); // Let user add them explicitly
        </script>
    @endpush
@endsection