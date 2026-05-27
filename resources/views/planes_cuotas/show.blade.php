@extends('layouts.app')
@section('title', 'Detalle Plan de Cuotas')
@section('page-title', 'Plan de Pagos')

@section('content')
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="flash-success" style="background:rgba(59,130,246,.12);border-color:#3b82f6;color:#3b82f6">{{ session('info') }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-ghost">← Volver a la venta</a>
        <h2 class="text-base" style="color:var(--text-muted)">Plan {{ $plan->tipo_plan }} — Venta #{{ $venta->numero_venta ?? $venta->id }}</h2>
        <span class="badge-status {{ $plan->estado === 'COMPLETADO' ? 'badge-disponible' : ($plan->estado === 'CANCELADO' ? 'badge-vendido' : 'badge-preparacion') }}">{{ $plan->estado }}</span>
    </div>

    {{-- Resumen estadístico --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @php
            $stats = [
                'Cliente' => $cliente->razon_social ?? '—',
                'Capital Financiado' => '$ ' . number_format($plan->capital_total_usd, 2, ',', '.') . ' USD',
                'Cuotas Pagadas' => $pagado . ' de ' . $cuotas->count(),
                'Vencidas' => $vencidas,
            ];
        @endphp
        @foreach($stats as $l => $v)
            <div class="rounded-xl border p-3 sm:p-4" style="background:var(--surface2);border-color:var(--border)">
                <div class="stat-label">{{ $l }}</div>
                <div class="font-bold text-accent text-base sm:text-lg">{{ $v }}</div>
            </div>
        @endforeach
    </div>

    {{-- Entregas iniciales --}}
    @if(isset($entregas) && $entregas->count() > 0)
        <div class="erp-card" style="margin-bottom:1.25rem">
            <div class="erp-card-header">
                <h2>💰 Entregas Iniciales</h2>
            </div>
            <div class="erp-card-body" style="padding:0">
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
            <h2>📅 Detalle de Cuotas</h2>
        </div>
        <div class="erp-card-body" style="padding:0">
            <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Cuota</th>
                        <th>Vencimiento</th>
                        <th>Capital</th>
                        <th>Interés</th>
                        <th>Total</th>
                        <th>Pagado</th>
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
                            <td><strong>$ {{ number_format($c->capital + $c->interes, 2, ',', '.') }}</strong></td>
                            <td>$ {{ number_format($c->monto_pagado, 2, ',', '.') }}</td>
                            <td>
                                @php $cs = match ($c->estado) { 'PAGADA' => 'badge-disponible', 'VENCIDA', 'EN_MORA' => 'badge-vendido', default => 'badge-preparacion'}; @endphp
                                <span class="badge-status {{ $cs }}">{{ $c->estado }}</span>
                            </td>
                            <td>
                                @if($c->estado === 'PENDIENTE' || $c->estado === 'VENCIDA' || $c->estado === 'EN_MORA')
                                    <button type="button" class="btn btn-ghost"
                                        style="padding:.25rem .5rem;font-size:.7rem;color:var(--success)"
                                        onclick="openPayModal({{ $c->id }}, {{ $c->numero_cuota }}, {{ $c->total_cuotas }}, '{{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}', {{ $c->capital }}, {{ $c->interes }}, {{ $c->capital + $c->interes }})">
                                        ✔ Pagar
                                    </button>
                                @elseif($c->estado === 'PAGADA')
                                    <div style="display:inline-flex;align-items:center;gap:.5rem">
                                        <span style="font-size:.75rem;color:var(--success)">{{ $c->fecha_pago_efectivo ? \Carbon\Carbon::parse($c->fecha_pago_efectivo)->format('d/m/Y') : '✔' }}</span>
                                        <a href="{{ route('cuotas.recibo-pdf', $c->id) }}" target="_blank"
                                           class="btn btn-ghost" style="padding:.2rem .4rem;font-size:.65rem;color:var(--text-muted)"
                                           title="Imprimir recibo PDF">🖨️</a>
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
        <div style="margin-top:1.5rem">
            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-ghost">👤 Ver Estado de Cuenta del Cliente →</a>
        </div>
    @endif

    {{-- Botón de liquidación del plan ─────────────────────────────────────── --}}
    @if($plan->estado === 'ACTIVO' && $cuotas->whereIn('estado', ['PENDIENTE','VENCIDA','EN_MORA','PAGADA_PARCIAL'])->count() > 0)
        <div style="margin-bottom:1.5rem;padding:1rem;background:var(--surface2);border-radius:.75rem;border:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
            <div>
                <strong style="color:var(--text)">💳 Liquidar Plan Completo</strong>
                <div style="font-size:.8rem;color:var(--text-muted)">Cancela todas las cuotas pendientes de una vez. Puede aplicar descuento sobre intereses futuros.</div>
            </div>
            <button type="button" class="btn btn-primary" onclick="openLiquidateModal({{ $plan->id }})" style="white-space:nowrap">
                Liquidar Plan
            </button>
        </div>
    @endif

    {{-- Modal de confirmación de pago de cuota individual --}}
    <div id="payModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:1rem;padding:1.5rem;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);max-height:90vh;overflow-y:auto">
            <h3 style="margin:0 0 1rem;font-size:1.1rem;color:var(--text)">Confirmar Pago de Cuota</h3>
            <div style="background:var(--surface2);border-radius:.5rem;padding:1rem;margin-bottom:1rem;font-size:.85rem;color:var(--text-muted)">
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Cuota:</span>
                    <strong id="modalCuotaNum" style="color:var(--text)"></strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Vencimiento:</span>
                    <strong id="modalVencimiento" style="color:var(--text)"></strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Capital:</span>
                    <span id="modalCapital"></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                    <span>Interés:</span>
                    <span id="modalInteres"></span>
                </div>
                <hr style="border-color:var(--border);margin:.5rem 0">
                <div style="display:flex;justify-content:space-between;font-size:.95rem">
                    <strong style="color:var(--text)">Total a pagar:</strong>
                    <strong id="modalTotal" style="color:var(--success)"></strong>
                </div>
            </div>

            {{-- Descuento por anticipo (opcional) --}}
            <div style="margin-bottom:1rem">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--text);cursor:pointer">
                    <input type="checkbox" id="chkDescuentoAnticipo" onchange="toggleDiscountFields()" style="accent-color:var(--accent)">
                    🏷️ Aplicar descuento por pronto pago
                </label>
                <div id="discountFields" style="display:none;margin-top:.5rem;padding:.75rem;background:var(--surface2);border-radius:.5rem">
                    <label style="font-size:.8rem;color:var(--text-muted)">% Descuento sobre capital:</label>
                    <input type="number" id="discountPct" value="{{ config('erp.installments.early_payment_discount.default_pct', 5) }}"
                           min="0" max="{{ config('erp.installments.early_payment_discount.max_pct', 15) }}" step="0.5"
                           style="width:70px;padding:.3rem;border:1px solid var(--border);border-radius:.3rem;background:var(--surface);color:var(--text);margin-left:.5rem">
                    <span style="font-size:.75rem;color:var(--text-muted);margin-left:.5rem" id="discountAmountLabel"></span>
                </div>
            </div>

            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem">
                Se registrará el pago con fecha de hoy ({{ date('d/m/Y') }}). Esta acción no se puede deshacer.
            </p>
            <div style="display:flex;gap:.5rem;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" onclick="closePayModal()">Cancelar</button>
                <form id="payForm" method="POST" action=""
                    onsubmit="var b=this.querySelector('button[type=submit]');b.disabled=true;b.textContent='⏳ Procesando...';b.style.opacity='.6'">
                    @csrf
                    <input type="hidden" name="fecha_pago" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="monto_pagado" id="payFormMonto" value="">
                    <input type="hidden" name="aplicar_descuento_anticipo" id="discountApplied" value="0">
                    <input type="hidden" name="descuento_anticipo_pct" id="discountPctForm" value="">
                    <input type="hidden" name="descuento_proporcional" value="0">
                    <button type="submit" class="btn btn-primary" style="font-size:.85rem">Confirmar Pago</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal de liquidación del plan ─────────────────────────────────────── --}}
    <div id="liquidateModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:1rem;padding:1.5rem;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);max-height:90vh;overflow-y:auto">
            <h3 style="margin:0 0 1rem;font-size:1.1rem;color:var(--text)">💳 Liquidar Plan de Cuotas</h3>
            <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem">
                Esta acción cancelará <strong>todas</strong> las cuotas pendientes del plan.
                Se calculará el saldo total incluyendo capital, intereses y mora acumulada.
            </p>

            <div style="margin-bottom:1rem">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--text);cursor:pointer">
                    <input type="checkbox" id="chkLiquidacionDiscount" onchange="toggleLiquidationFields()" style="accent-color:var(--accent)">
                    🏷️ Aplicar descuento por liquidación (sobre intereses no devengados)
                </label>
                <div id="liquidationDiscountFields" style="display:none;margin-top:.5rem;padding:.75rem;background:var(--surface2);border-radius:.5rem">
                    <label style="font-size:.8rem;color:var(--text-muted)">% Descuento:</label>
                    <input type="number" id="liquidationDiscountPct" value="{{ config('erp.installments.liquidation_discount.default_pct', 20) }}"
                           min="0" max="{{ config('erp.installments.liquidation_discount.max_pct', 50) }}" step="0.5"
                           style="width:70px;padding:.3rem;border:1px solid var(--border);border-radius:.3rem;background:var(--surface);color:var(--text);margin-left:.5rem">
                    <span style="font-size:.75rem;color:var(--text-muted);margin-left:.5rem">
                        (máx {{ config('erp.installments.liquidation_discount.max_pct', 50) }}%)
                    </span>
                </div>
            </div>

            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem">
                ⚠️ Esta acción es irreversible. Se generará un recibo de liquidación.
            </p>
            <div style="display:flex;gap:.5rem;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" onclick="closeLiquidateModal()">Cancelar</button>
                <form id="liquidateForm" method="POST" action=""
                    onsubmit="var b=this.querySelector('button[type=submit]');b.disabled=true;b.textContent='⏳ Procesando...';b.style.opacity='.6'">
                    @csrf
                    <input type="hidden" name="fecha_liquidacion" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="aplicar_descuento_liquidacion" id="liqDiscountApplied" value="0">
                    <input type="hidden" name="descuento_liquidacion_pct" id="liqDiscountPctForm" value="">
                    <button type="submit" class="btn btn-primary" style="font-size:.85rem">Confirmar Liquidación</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function formatMoney(n) {
            return '$ ' + Number(n).toLocaleString('es-PY', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' USD';
        }

        let currentCapital = 0, currentMonto = 0;

        function openPayModal(id, num, total, venc, capital, interes, monto) {
            let url = "{{ route('cuotas.pagar', ':id') }}";
            url = url.replace(':id', id);
            document.getElementById('payForm').action = url;
            document.getElementById('payFormMonto').value = monto;
            document.getElementById('modalCuotaNum').textContent = num + '/' + total;
            document.getElementById('modalVencimiento').textContent = venc;
            document.getElementById('modalCapital').textContent = formatMoney(capital);
            document.getElementById('modalInteres').textContent = formatMoney(interes);
            document.getElementById('modalTotal').textContent = formatMoney(monto);
            currentCapital = capital;
            currentMonto = monto;
            document.getElementById('payModal').style.display = 'flex';
            // Reset discount fields
            document.getElementById('chkDescuentoAnticipo').checked = false;
            document.getElementById('discountFields').style.display = 'none';
            document.getElementById('discountApplied').value = '0';
            updateDiscountLabel();
        }

        function toggleDiscountFields() {
            let checked = document.getElementById('chkDescuentoAnticipo').checked;
            document.getElementById('discountFields').style.display = checked ? 'block' : 'none';
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

        function closePayModal() {
            document.getElementById('payModal').style.display = 'none';
        }

        // ── Liquidación ─────────────────────────────────────────────────
        function openLiquidateModal(planId) {
            let url = "{{ route('planes_cuotas.liquidar', ':id') }}";
            url = url.replace(':id', planId);
            document.getElementById('liquidateForm').action = url;
            document.getElementById('liquidateModal').style.display = 'flex';
            document.getElementById('chkLiquidacionDiscount').checked = false;
            document.getElementById('liquidationDiscountFields').style.display = 'none';
            document.getElementById('liqDiscountApplied').value = '0';
        }

        function toggleLiquidationFields() {
            let checked = document.getElementById('chkLiquidacionDiscount').checked;
            document.getElementById('liquidationDiscountFields').style.display = checked ? 'block' : 'none';
            document.getElementById('liqDiscountApplied').value = checked ? '1' : '0';
            let pct = parseFloat(document.getElementById('liquidationDiscountPct').value) || 0;
            document.getElementById('liqDiscountPctForm').value = pct;
        }

        document.getElementById('liquidationDiscountPct').addEventListener('input', function() {
            let pct = parseFloat(this.value) || 0;
            document.getElementById('liqDiscountPctForm').value = pct;
        });

        function closeLiquidateModal() {
            document.getElementById('liquidateModal').style.display = 'none';
        }

        // Close modals on overlay click or Escape
        [document.getElementById('payModal'), document.getElementById('liquidateModal')].forEach(function(m) {
            m.addEventListener('click', function(e) {
                if (e.target === this) { this.style.display = 'none'; }
            });
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('payModal').style.display = 'none';
                document.getElementById('liquidateModal').style.display = 'none';
            }
        });
    </script>

    @if(session('show_print_cuota'))
        <div id="printReceiptToast" class="erp-toast-banner">
            <div class="erp-toast-icon">🎉</div>
            <div class="erp-toast-content">
                <strong>¡Pago registrado con éxito!</strong>
                <span>El recibo de pago ya está listo para ser impreso.</span>
            </div>
            <div class="erp-toast-actions">
                <a href="{{ route('cuotas.recibo-pdf', session('show_print_cuota')) }}" target="_blank" class="btn btn-primary btn-sm" onclick="closeReceiptToast()" style="white-space:nowrap;padding:.4rem .75rem;font-size:.8rem">
                    🖨️ Imprimir Recibo
                </a>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeReceiptToast()" style="color:var(--text-muted);padding:.4rem .75rem;font-size:.8rem">
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