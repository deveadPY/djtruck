@extends('layouts.app')
@section('title', 'Detalle Plan de Cuotas')
@section('page-title', 'Plan de Pagos')

@section('content')

    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver a la venta</a>
        <h2 class="text-base text-muted-foreground">Plan {{ $plan->tipo_plan }} — Venta #{{ $venta->numero_venta ?? $venta->id }}</h2>
        <span class="badge-status {{ $plan->estado === 'COMPLETADO' ? 'badge-disponible' : ($plan->estado === 'CANCELADO' ? 'badge-vendido' : 'badge-preparacion') }}">{{ $plan->estado }}</span>
    </div>

    {{-- Resumen estadístico --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @php
            $stats = [
                'Cliente' => $cliente->razon_social ?? '—',
                'Capital Financiado' => '$ ' . number_format($plan->capital_total_usd, 2, ',', '.') . ' USD',
                'Saldo pendiente' => '$ ' . number_format($saldoPendiente, 2, ',', '.') . ' USD',
                'Próximo vencimiento' => $proxima
                    ? \Carbon\Carbon::parse($proxima->fecha_vencimiento)->format('d/m/Y') . ' · $ ' . number_format($proxima->saldo, 2, ',', '.')
                    : '—',
                'Cuotas Pagadas' => $pagado . ' de ' . $cuotas->count(),
                'Vencidas' => $vencidas,
            ];
        @endphp
        @foreach($stats as $l => $v)
            <div class="rounded-xl border border-border bg-surface-2 p-3 sm:p-4">
                <div class="stat-label">{{ $l }}</div>
                <div class="font-bold text-accent text-base sm:text-lg">{{ $v }}</div>
            </div>
        @endforeach
    </div>

    {{-- Entregas iniciales --}}
    @if(isset($entregas) && $entregas->count() > 0)
        <div class="erp-card mb-5">
            <div class="erp-card-header">
                <h2>Entregas Iniciales</h2>
            </div>
            <div class="erp-card-body !p-0">
                <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Monto USD</th>
                            <th>Fecha</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entregas as $e)
                            <tr>
                                <td>{{ $e->tipo_pago }}</td>
                                <td>$ {{ number_format($e->monto_usd, 2, ',', '.') }}</td>
                                <td>{{ \Carbon\Carbon::parse($e->fecha_pago)->format('d/m/Y') }}</td>
                                <td>{{ $e->referencia_bancaria ?: $e->observaciones ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Grilla de cuotas --}}
    <div class="erp-card">
        <div class="erp-card-header">
            <h2>Detalle de Cuotas</h2>
        </div>
        <div class="erp-card-body !p-0">
            <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Cuota</th>
                        <th>Vencimiento</th>
                        <th>Capital</th>
                        <th>Interés</th>
                        <th>Mora</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cuotas as $c)
                        <tr>
                            <td>{{ $c->numero_cuota }}/{{ $c->total_cuotas }}</td>
                            <td>{{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}</td>
                            <td>$ {{ number_format($c->capital, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($c->interes, 2, ',', '.') }}</td>
                            <td @class(['text-[var(--danger)]' => $c->interes_mora > 0])>$ {{ number_format($c->interes_mora, 2, ',', '.') }}</td>
                            <td><strong>$ {{ number_format($c->capital + $c->interes + $c->interes_mora, 2, ',', '.') }}</strong></td>
                            <td>$ {{ number_format($c->monto_pagado, 2, ',', '.') }}</td>
                            <td><strong>$ {{ number_format($c->saldo, 2, ',', '.') }}</strong></td>
                            <td>
                                @php $cs = match ($c->estado) { 'PAGADA' => 'badge-disponible', 'VENCIDA', 'EN_MORA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                <span class="badge-status {{ $cs }}">{{ $c->estado }}</span>
                            </td>
                            <td>
                                @if(in_array($c->estado, ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL']) && $c->saldo > 0)
                                    <button type="button" class="btn btn-ghost !px-2 !py-1 text-xs text-[var(--success)]"
                                        onclick="openPayModal({{ $c->id }}, {{ $c->numero_cuota }}, {{ $c->total_cuotas }}, '{{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}', {{ (float) $c->capital }}, {{ (float) $c->interes }}, {{ (float) $c->interes_mora }}, {{ (float) $c->monto_pagado }}, {{ $c->saldo }})">
                                        {{ $c->estado === 'PAGADA_PARCIAL' ? 'Cobrar saldo' : 'Pagar' }}
                                    </button>
                                @elseif($c->estado === 'PAGADA')
                                    <div class="inline-flex items-center gap-2">
                                        <span class="text-xs text-[var(--success)]">{{ $c->fecha_pago_efectivo ? \Carbon\Carbon::parse($c->fecha_pago_efectivo)->format('d/m/Y') : '✓' }}</span>
                                        <a href="{{ route('cuotas.recibo-pdf', $c->id) }}" target="_blank"
                                           class="btn btn-ghost !px-1.5 !py-1 text-muted-foreground"
                                           title="Imprimir recibo PDF" aria-label="Imprimir recibo PDF"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg></a>
                                        @if($c->whatsapp_url)
                                            <a href="{{ $c->whatsapp_url }}" target="_blank" rel="noopener"
                                               class="btn btn-ghost !px-1.5 !py-1 text-xs text-[var(--success)]">Enviar por WhatsApp</a>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Link to Client Dashboard --}}
    @if($cliente)
        <div class="mt-6">
            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-ghost">Ver Estado de Cuenta del Cliente →</a>
        </div>
    @endif

    {{-- Botón de liquidación del plan ─────────────────────────────────────── --}}
    @if($plan->estado === 'ACTIVO' && $cuotas->whereIn('estado', ['PENDIENTE','VENCIDA','EN_MORA','PAGADA_PARCIAL'])->count() > 0)
        <div class="mb-6 p-4 bg-surface-2 rounded-xl border border-border flex items-center justify-between flex-wrap gap-3">
            <div>
                <strong class="text-foreground">Liquidar Plan Completo</strong>
                <div class="text-xs text-muted-foreground">Cancela todas las cuotas pendientes de una vez. Puede aplicar descuento sobre intereses futuros.</div>
            </div>
            <button type="button" class="btn btn-primary whitespace-nowrap" onclick="openLiquidateModal({{ $plan->id }})">
                Liquidar Plan
            </button>
        </div>
    @endif

    {{-- Modal de confirmación de pago de cuota individual --}}
    <div id="payModal" class="hidden fixed inset-0 z-[9999] bg-black/50 items-center justify-center">
        <div role="dialog" aria-modal="true" aria-labelledby="payModalTitle" class="bg-surface border border-border rounded-2xl p-6 max-w-[440px] w-[90%] shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 id="payModalTitle" class="mb-4 text-lg text-foreground">Confirmar Pago de Cuota</h3>
            <div class="bg-surface-2 rounded-lg p-4 mb-4 text-sm text-muted-foreground">
                <div class="flex justify-between mb-2">
                    <span>Cuota:</span>
                    <strong id="modalCuotaNum" class="text-foreground"></strong>
                </div>
                <div class="flex justify-between mb-2">
                    <span>Vencimiento:</span>
                    <strong id="modalVencimiento" class="text-foreground"></strong>
                </div>
                <div class="flex justify-between mb-2">
                    <span>Capital:</span>
                    <span id="modalCapital"></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span>Interés:</span>
                    <span id="modalInteres"></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span>Mora:</span>
                    <span id="modalMora"></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span>Ya pagado:</span>
                    <span id="modalPagado"></span>
                </div>
                <hr class="border-border my-2">
                <div class="flex justify-between text-base">
                    <strong class="text-foreground">Saldo a cobrar:</strong>
                    <strong id="modalTotal" class="text-[var(--success)]"></strong>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label for="payFormMonto" class="form-label">Monto a cobrar (USD)</label>
                    <input type="number" name="monto_pagado" id="payFormMonto" form="payForm" class="form-input w-full"
                           step="0.01" min="0.01" max="" required>
                </div>
                <div>
                    <label for="payFormFecha" class="form-label">Fecha de pago</label>
                    <input type="date" name="fecha_pago" id="payFormFecha" form="payForm" class="form-input w-full"
                           value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            {{-- Descuento por anticipo (opcional) --}}
            <div class="mb-4">
                <label class="flex items-center gap-2 text-sm text-foreground cursor-pointer">
                    <input type="checkbox" id="chkDescuentoAnticipo" onchange="toggleDiscountFields()" class="accent-[var(--accent)]">
                    Aplicar descuento por pronto pago
                </label>
                <div id="discountFields" class="hidden mt-2 p-3 bg-surface-2 rounded-lg">
                    <label class="text-xs text-muted-foreground">% Descuento sobre capital:</label>
                    <input type="number" id="discountPct" value="{{ config('erp.installments.early_payment_discount.default_pct', 5) }}"
                           min="0" max="{{ config('erp.installments.early_payment_discount.max_pct', 15) }}" step="0.5"
                           class="w-[70px] p-1 border border-border rounded bg-surface text-foreground ml-2">
                    <span class="text-xs text-muted-foreground ml-2" id="discountAmountLabel"></span>
                </div>
            </div>

            <p class="text-xs text-muted-foreground mb-4">
                Si cobrás menos del saldo, la cuota queda como pago parcial. Esta acción no se puede deshacer.
            </p>
            <div class="flex gap-2 justify-end">
                <button type="button" class="btn btn-ghost" onclick="closePayModal()">Cancelar</button>
                <form id="payForm" method="POST" action=""
                    onsubmit="return lockSubmit(this)">
                    @csrf
                    <input type="hidden" name="aplicar_descuento_anticipo" id="discountApplied" value="0">
                    <input type="hidden" name="descuento_anticipo_pct" id="discountPctForm" value="">
                    <input type="hidden" name="descuento_proporcional" value="0">
                    <button type="submit" class="btn btn-primary text-sm disabled:opacity-60">Confirmar Pago</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal de liquidación del plan ─────────────────────────────────────── --}}
    <div id="liquidateModal" class="hidden fixed inset-0 z-[9999] bg-black/50 items-center justify-center">
        <div role="dialog" aria-modal="true" aria-labelledby="liquidateModalTitle" class="bg-surface border border-border rounded-2xl p-6 max-w-[480px] w-[90%] shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 id="liquidateModalTitle" class="mb-4 text-lg text-foreground">Liquidar Plan de Cuotas</h3>
            <p class="text-sm text-muted-foreground mb-4">
                Esta acción cancelará <strong>todas</strong> las cuotas pendientes del plan.
                Se calculará el saldo total incluyendo capital, intereses y mora acumulada.
            </p>

            <div class="mb-4">
                <label class="flex items-center gap-2 text-sm text-foreground cursor-pointer">
                    <input type="checkbox" id="chkLiquidacionDiscount" onchange="toggleLiquidationFields()" class="accent-[var(--accent)]">
                    Aplicar descuento por liquidación (sobre intereses no devengados)
                </label>
                <div id="liquidationDiscountFields" class="hidden mt-2 p-3 bg-surface-2 rounded-lg">
                    <label class="text-xs text-muted-foreground">% Descuento:</label>
                    <input type="number" id="liquidationDiscountPct" value="{{ config('erp.installments.liquidation_discount.default_pct', 20) }}"
                           min="0" max="{{ config('erp.installments.liquidation_discount.max_pct', 50) }}" step="0.5"
                           class="w-[70px] p-1 border border-border rounded bg-surface text-foreground ml-2">
                    <span class="text-xs text-muted-foreground ml-2">
                        (máx {{ config('erp.installments.liquidation_discount.max_pct', 50) }}%)
                    </span>
                </div>
            </div>

            <p class="text-xs text-muted-foreground mb-4">
                Esta acción es irreversible. Se generará un recibo de liquidación.
            </p>
            <div class="flex gap-2 justify-end">
                <button type="button" class="btn btn-ghost" onclick="closeLiquidateModal()">Cancelar</button>
                <form id="liquidateForm" method="POST" action=""
                    onsubmit="return lockSubmit(this)">
                    @csrf
                    <input type="hidden" name="fecha_liquidacion" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="aplicar_descuento_liquidacion" id="liqDiscountApplied" value="0">
                    <input type="hidden" name="descuento_liquidacion_pct" id="liqDiscountPctForm" value="">
                    <button type="submit" class="btn btn-primary text-sm disabled:opacity-60">Confirmar Liquidación</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function formatMoney(n) {
            return '$ ' + Number(n).toLocaleString('es-PY', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' USD';
        }

        let currentCapital = 0, currentMonto = 0;

        function openPayModal(id, num, total, venc, capital, interes, mora, pagado, saldo) {
            let url = "{{ route('cuotas.pagar', ':id') }}";
            url = url.replace(':id', id);
            document.getElementById('payForm').action = url;
            const montoInput = document.getElementById('payFormMonto');
            montoInput.value = saldo.toFixed(2);
            montoInput.max = saldo.toFixed(2);
            document.getElementById('payFormFecha').value = "{{ date('Y-m-d') }}";
            document.getElementById('modalCuotaNum').textContent = num + '/' + total;
            document.getElementById('modalVencimiento').textContent = venc;
            document.getElementById('modalCapital').textContent = formatMoney(capital);
            document.getElementById('modalInteres').textContent = formatMoney(interes);
            document.getElementById('modalMora').textContent = formatMoney(mora);
            document.getElementById('modalPagado').textContent = formatMoney(pagado);
            document.getElementById('modalTotal').textContent = formatMoney(saldo);
            const monto = saldo;
            currentCapital = capital;
            currentMonto = monto;
            openModal('payModal');
            // Reset discount fields
            document.getElementById('chkDescuentoAnticipo').checked = false;
            document.getElementById('discountFields').classList.add('hidden');
            document.getElementById('discountApplied').value = '0';
            updateDiscountLabel();
        }

        function toggleDiscountFields() {
            let checked = document.getElementById('chkDescuentoAnticipo').checked;
            document.getElementById('discountFields').classList.toggle('hidden', !checked);
            document.getElementById('discountApplied').value = checked ? '1' : '0';
            updateDiscountLabel();
        }

        function updateDiscountLabel() {
            let pct = parseFloat(document.getElementById('discountPct').value) || 0;
            let discountAmount = currentCapital * (pct / 100);
            document.getElementById('discountAmountLabel').textContent =
                '≈ $' + discountAmount.toFixed(2) + ' de descuento';
            document.getElementById('discountPctForm').value = pct;
        }

        document.getElementById('discountPct').addEventListener('input', updateDiscountLabel);

        function closePayModal() { closeModal('payModal'); }

        // ── Liquidación ─────────────────────────────────────────────────
        function openLiquidateModal(planId) {
            let url = "{{ route('planes_cuotas.liquidar', ':id') }}";
            url = url.replace(':id', planId);
            document.getElementById('liquidateForm').action = url;
            openModal('liquidateModal');
            document.getElementById('chkLiquidacionDiscount').checked = false;
            document.getElementById('liquidationDiscountFields').classList.add('hidden');
            document.getElementById('liqDiscountApplied').value = '0';
        }

        function toggleLiquidationFields() {
            let checked = document.getElementById('chkLiquidacionDiscount').checked;
            document.getElementById('liquidationDiscountFields').classList.toggle('hidden', !checked);
            document.getElementById('liqDiscountApplied').value = checked ? '1' : '0';
            let pct = parseFloat(document.getElementById('liquidationDiscountPct').value) || 0;
            document.getElementById('liqDiscountPctForm').value = pct;
        }

        document.getElementById('liquidationDiscountPct').addEventListener('input', function() {
            let pct = parseFloat(this.value) || 0;
            document.getElementById('liqDiscountPctForm').value = pct;
        });

        function closeLiquidateModal() { closeModal('liquidateModal'); }

        // ── Modales accesibles: foco al abrir, vuelve al disparador al cerrar ──
        let modalTrigger = null;
        function openModal(id) {
            modalTrigger = document.activeElement;
            const m = document.getElementById(id);
            m.classList.remove('hidden');
            m.classList.add('flex');
            const first = m.querySelector('input:not([type=hidden]), button');
            if (first) first.focus();
        }
        function closeModal(id) {
            const m = document.getElementById(id);
            if (m.classList.contains('hidden')) return;
            m.classList.add('hidden');
            m.classList.remove('flex');
            if (modalTrigger) modalTrigger.focus();
        }
        // Evita doble cobro: el botón queda deshabilitado tras el primer envío
        function lockSubmit(form) {
            const b = form.querySelector('button[type=submit]');
            if (b.disabled) return false;
            b.disabled = true;
            b.textContent = 'Procesando...';
            return true;
        }

        // Close modals on overlay click or Escape
        ['payModal', 'liquidateModal'].forEach(function(id) {
            document.getElementById(id).addEventListener('click', function(e) {
                if (e.target === this) { closeModal(id); }
            });
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('payModal');
                closeModal('liquidateModal');
            }
        });
    </script>

    @if(session('show_print_cuota'))
        <div id="printReceiptToast" class="erp-toast-banner">
            <div class="erp-toast-icon"><svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></div>
            <div class="erp-toast-content">
                <strong>¡Pago registrado con éxito!</strong>
                <span>El recibo de pago ya está listo para ser impreso.</span>
            </div>
            <div class="erp-toast-actions">
                <a href="{{ route('cuotas.recibo-pdf', session('show_print_cuota')) }}" target="_blank" class="btn btn-primary btn-sm whitespace-nowrap !px-3 !py-1.5 text-xs" onclick="closeReceiptToast()">
                    Imprimir Recibo
                </a>
                @php $cuotaCobrada = $cuotas->firstWhere('id', (int) session('show_print_cuota')); @endphp
                @if($cuotaCobrada?->whatsapp_url)
                    <a href="{{ $cuotaCobrada->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm whitespace-nowrap !px-3 !py-1.5 text-xs text-[var(--success)]">
                        Enviar por WhatsApp
                    </a>
                @endif
                <button type="button" class="btn btn-ghost btn-sm text-muted-foreground !px-3 !py-1.5 text-xs" onclick="closeReceiptToast()">
                    Descartar
                </button>
            </div>
        </div>
        
        <style>
            .erp-toast-banner {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 99999;
                display: flex;
                align-items: center;
                gap: 1rem;
                background: var(--surface);
                border: 1px solid var(--border);
                border-left: 4px solid var(--success);
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                border-radius: 0.75rem;
                padding: 1rem 1.25rem;
                max-width: 480px;
                width: calc(100% - 40px);
                animation: toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .erp-toast-icon {
                font-size: 1.5rem;
            }
            .erp-toast-content {
                display: flex;
                flex-direction: column;
                gap: 0.2rem;
                flex-grow: 1;
            }
            .erp-toast-content strong {
                color: var(--text);
                font-size: 0.9rem;
            }
            .erp-toast-content span {
                color: var(--text-muted);
                font-size: 0.8rem;
            }
            .erp-toast-actions {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            @keyframes toastSlideIn {
                from {
                    transform: translateY(-20px) scale(0.95);
                    opacity: 0;
                }
                to {
                    transform: translateY(0) scale(1);
                    opacity: 1;
                }
            }
            @media (max-width: 640px) {
                .erp-toast-banner {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0.75rem;
                    top: 10px;
                    right: 10px;
                    width: calc(100% - 20px);
                }
                .erp-toast-actions {
                    width: 100%;
                    justify-content: flex-end;
                }
            }
        </style>
        <script>
            function closeReceiptToast() {
                let toast = document.getElementById('printReceiptToast');
                if (toast) {
                    toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px) scale(0.95)';
                    setTimeout(() => toast.remove(), 200);
                }
            }
            // Auto-close after 15 seconds if not interacted
            setTimeout(closeReceiptToast, 15000);
        </script>
    @endif
@endsection