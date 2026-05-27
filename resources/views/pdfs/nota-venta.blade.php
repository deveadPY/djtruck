<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Venta {{ $venta->numero_venta }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #374151;
            background: #fff;
            padding: 15px 25px;
        }
        .header { width: 100%; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
        .header td { vertical-align: middle; }
        .company-name { font-size: 16px; font-weight: bold; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; }
        .company-info { font-size: 8px; color: #6b7280; margin-top: 2px; line-height: 1.3; }
        .doc-title { text-align: right; }
        .doc-label { font-size: 12px; font-weight: bold; color: #111827; text-transform: uppercase; letter-spacing: 1px; }
        .doc-number { font-size: 14px; font-weight: bold; color: #991b1b; margin-top: 2px; }
        
        .section-title { 
            font-size: 9px; 
            font-weight: bold; 
            background: #f9fafb; 
            color: #111827;
            padding: 4px 8px; 
            margin-top: 15px; 
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 3px solid #991b1b;
        }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 4px 0; vertical-align: top; border-bottom: 1px solid #f3f4f6; }
        .label { color: #6b7280; width: 100px; font-size: 8px; text-transform: uppercase; font-weight: 600; }
        .value { font-weight: 600; color: #111827; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { 
            background: #f9fafb; 
            color: #4b5563; 
            padding: 8px 6px; 
            text-align: left; 
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table td { padding: 8px 6px; border-bottom: 1px solid #f3f4f6; font-size: 9px; color: #374151; }
        .text-right { text-align: right; }
        
        .totals-table { width: 220px; margin-left: auto; margin-top: 15px; border-collapse: collapse; }
        .totals-table td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; font-size: 9px; }
        .total-row { font-size: 11px; font-weight: bold; color: #991b1b; background: #fef2f2; }
        .total-row td { padding: 10px 6px; border-top: 1px solid #fee2e2; border-bottom: 1px solid #fee2e2; }
        
        .footer { margin-top: 30px; }
        .signature-table { width: 100%; margin-top: 30px; }
        .signature-box { border-top: 1px solid #d1d5db; width: 180px; text-align: center; padding-top: 6px; font-size: 8px; color: #4b5563; }
        
        .payment-info { margin-top: 15px; font-size: 8px; }
        .payment-info table { width: 100%; border-collapse: collapse; }
        .payment-info th { background: #f9fafb; padding: 6px; text-align: left; border: 1px solid #e5e7eb; font-weight: bold; color: #6b7280; }
        .payment-info td { padding: 4px 6px; border: 1px solid #e5e7eb; color: #4b5563; }

        .cuotas-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 8px; }
        .cuotas-table th {
            background: #1f2937;
            color: #f9fafb;
            padding: 6px 5px;
            text-align: left;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .cuotas-table th.text-right { text-align: right; }
        .cuotas-table td { padding: 5px 5px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
        .cuotas-table td.text-right { text-align: right; font-family: monospace; }
        .cuotas-table tr:nth-child(even) td { background: #f9fafb; }
        .cuotas-table tr { page-break-inside: avoid; }
        .cuotas-table tr.cuota-pagada td { color: #9ca3af; }
        .badge-estado { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .badge-pendiente { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-pagada    { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-vencida   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-mora      { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .plan-summary { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8px; }
        .plan-summary td { padding: 4px 8px; border: 1px solid #e5e7eb; }
        .plan-summary td.ps-label { background: #f9fafb; color: #6b7280; font-weight: bold; text-transform: uppercase; width: 120px; }
        .plan-summary td.ps-value { color: #111827; font-weight: 600; }
        .cuotas-footer { background: #f1f5f9; }
        .cuotas-footer td { padding: 6px 5px; border-top: 2px solid #334155; font-weight: bold; color: #0f172a; font-size: 8.5px; }
        
        .watermark {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px; color: rgba(153, 27, 27, 0.02); z-index: -1; font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="watermark">NOTA DE VENTA</div>

    <table class="header">
        <tr>
            <td style="width: 150px;">
                @if($empresa->logo_path)
                    <img src="{{ $empresa->logoAbsPath() }}" alt="Logo" style="max-height: 80px; max-width: 140px;">
                @else
                    <div style="width: 70px; height: 70px; background: #374151; color: #fff; text-align: center; line-height: 70px; font-weight: bold; font-size: 20px; border-radius: 4px;">DJ</div>
                @endif
            </td>
            <td>
                <div class="company-name">{{ $empresa->nombre_empresa ?? 'DJ TRUCKS & CARS' }}</div>
                <div class="company-info">
                    @if($empresa->ruc) RUC: {{ $empresa->ruc }}<br> @endif
                    @if($empresa->direccion) {{ $empresa->direccion }}<br> @endif
                    @if($empresa->telefono) Tel: {{ $empresa->telefono }} @endif
                </div>
            </td>
            <td class="doc-title">
                <div class="doc-label">Nota de Venta</div>
                <div class="doc-number">{{ $venta->numero_venta }}</div>
                <div class="company-info">Fecha: {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                <div class="section-title">DATOS DEL CLIENTE</div>
                <table class="info-table">
                    <tr><td class="label">Razón Social:</td><td class="value">{{ $cliente->razon_social }}</td></tr>
                    <tr><td class="label">RUC/CI:</td><td class="value">{{ $cliente->ruc ?? $cliente->documento_numero }}</td></tr>
                    <tr><td class="label">Dirección:</td><td class="value">{{ $cliente->direccion ?? 'N/A' }}</td></tr>
                    <tr><td class="label">Teléfono:</td><td class="value">{{ $cliente->telefono ?? 'N/A' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="section-title">DATOS DE LA OPERACIÓN</div>
                <table class="info-table">
                    <tr><td class="label">Vendedor:</td><td class="value">{{ $venta->vendedor->name ?? 'N/A' }}</td></tr>
                    <tr><td class="label">Condición:</td><td class="value">{{ $venta->modalidad_pago }}</td></tr>
                    <tr><td class="label">Moneda:</td><td class="value">{{ $venta->moneda_venta }}</td></tr>
                    <tr><td class="label">Tasa Cambio:</td><td class="value">{{ number_format((float)$venta->tasa_cambio_venta, 0, ',', '.') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">DETALLE DE PRODUCTOS / SERVICIOS</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->descripcion }}</td>
                <td class="text-right">{{ number_format((float)$item->cantidad, 0) }}</td>
                <td class="text-right">
                    @if($venta->moneda_venta === 'PYG')
                        {{ number_format((float)$item->precio_unitario_moneda, 0, ',', '.') }}
                    @else
                        {{ number_format((float)$item->precio_unitario_usd, 2, ',', '.') }}
                    @endif
                </td>
                <td class="text-right">
                    @if($venta->moneda_venta === 'PYG')
                        {{ number_format((float)$item->subtotal_moneda, 0, ',', '.') }}
                    @else
                        {{ number_format((float)$item->subtotal_usd, 2, ',', '.') }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        @if($venta->descuento_usd > 0)
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">
                @if($venta->moneda_venta === 'PYG')
                    {{ number_format((float)$venta->precio_venta_moneda, 0, ',', '.') }}
                @else
                    {{ number_format((float)$venta->precio_venta_usd, 2, ',', '.') }}
                @endif
            </td>
        </tr>
        <tr>
            <td>Descuento:</td>
            <td class="text-right">
                @if($venta->moneda_venta === 'PYG')
                    -{{ number_format((float)$venta->descuento_moneda, 0, ',', '.') }}
                @else
                    -{{ number_format((float)$venta->descuento_usd, 2, ',', '.') }}
                @endif
            </td>
        </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL A PAGAR:</td>
            <td class="text-right">
                @php
                    $totalMoneda = (float)$venta->precio_venta_moneda - (float)($venta->descuento_moneda ?? 0);
                    $totalUsd = (float)$venta->precio_venta_usd - (float)($venta->descuento_usd ?? 0);
                @endphp
                @if($venta->moneda_venta === 'PYG')
                    {{ $venta->moneda_venta }} {{ number_format($totalMoneda, 0, ',', '.') }}
                @else
                    {{ $venta->moneda_venta }} {{ number_format($totalUsd, 2, ',', '.') }}
                @endif
            </td>
        </tr>
    </table>

    @if(count($pagos) > 0)
    <div class="section-title">RESUMEN DE PAGOS / ENTREGAS</div>
    <div class="payment-info">
        <table>
            <thead>
                <tr>
                    <th>TIPO DE PAGO</th>
                    <th>FECHA</th>
                    <th>REFERENCIA</th>
                    <th class="text-right">MONTO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagos as $pago)
                <tr>
                    <td style="font-weight: bold;">{{ $pago->tipo_pago }}</td>
                    <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                    <td>{{ $pago->referencia_bancaria ?? ($pago->tipo_pago === 'PLAN_CUOTAS' ? 'Financiación Propia' : '-') }}</td>
                    <td class="text-right" style="font-weight: bold;">
                        @if($venta->moneda_venta === 'PYG')
                            {{ number_format((float)$pago->monto_moneda, 0, ',', '.') }}
                        @else
                            {{ number_format((float)$pago->monto_usd, 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($plan && $cuotas->count() > 0)
    @php
        $monedaCuota  = $cuotas->first()->moneda ?? $venta->moneda_venta;
        $esPyg        = $monedaCuota === 'PYG';
        $sym          = $esPyg ? 'Gs.' : 'USD';
        $dec          = $esPyg ? 0 : 2;
        $totalCapital = $cuotas->sum(fn($c) => (float)$c->capital);
        $totalInteres = $cuotas->sum(fn($c) => (float)$c->interes);
        $totalCuota   = $totalCapital + $totalInteres;
        $cuotasPagadas = $cuotas->where('estado', 'PAGADA')->count();
    @endphp

    @php
        $tipoPlanLabel = match($plan->tipo_plan) {
            'FRANCESA' => 'Cuota Fija',
            'MANUAL'   => 'Manual / Personalizado',
            default    => $plan->tipo_plan,
        };
    @endphp

    <div class="section-title">
        PLAN DE FINANCIACIÓN — {{ $tipoPlanLabel }}
        &nbsp;|&nbsp; {{ $cuotas->count() }} cuotas
        @if($cuotasPagadas > 0)
            &nbsp;|&nbsp; {{ $cuotasPagadas }} pagada(s)
        @endif
    </div>

    {{-- Resumen del plan --}}
    <table class="plan-summary">
        <tr>
            <td class="ps-label">Modalidad</td>
            <td class="ps-value">{{ $tipoPlanLabel }}</td>
            <td class="ps-label">Capital financiado</td>
            <td class="ps-value">{{ $sym }} {{ number_format((float)$plan->capital_total_usd ?? $totalCapital, $dec, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="ps-label">N° cuotas</td>
            <td class="ps-value">{{ $cuotas->count() }}</td>
            <td class="ps-label">Tasa mensual</td>
            <td class="ps-value">{{ $plan->tasa_interes_mensual ?? '—' }}%</td>
        </tr>
        <tr>
            <td class="ps-label">1ª vencimiento</td>
            <td class="ps-value">{{ \Carbon\Carbon::parse($cuotas->first()->fecha_vencimiento)->format('d/m/Y') }}</td>
            <td class="ps-label">Último vencimiento</td>
            <td class="ps-value">{{ \Carbon\Carbon::parse($cuotas->last()->fecha_vencimiento)->format('d/m/Y') }}</td>
        </tr>
    </table>

    {{-- Tabla de cuotas --}}
    <table class="cuotas-table">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th style="width:60px;">Vencimiento</th>
                <th style="width:50px;">Tipo</th>
                <th class="text-right">Capital</th>
                <th class="text-right">Interés</th>
                <th class="text-right">Total cuota</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuotas as $cuota)
            @php
                $esPagada = $cuota->estado === 'PAGADA';
                $esVencida = in_array($cuota->estado, ['VENCIDA', 'EN_MORA']);
                $totalFila = (float)$cuota->capital + (float)$cuota->interes;
                $badgeClass = match(true) {
                    $cuota->estado === 'PAGADA'  => 'badge-pagada',
                    $cuota->estado === 'VENCIDA' => 'badge-vencida',
                    $cuota->estado === 'EN_MORA' => 'badge-mora',
                    default                      => 'badge-pendiente',
                };
                $estadoLabel = match($cuota->estado) {
                    'PAGADA'   => 'Pagada',
                    'VENCIDA'  => 'Vencida',
                    'EN_MORA'  => 'En mora',
                    'PENDIENTE'=> 'Pendiente',
                    default    => $cuota->estado,
                };
            @endphp
            <tr class="{{ $esPagada ? 'cuota-pagada' : '' }}">
                <td style="font-weight:bold; color:{{ $esPagada ? '#9ca3af' : '#374151' }}">{{ $cuota->numero_cuota }}</td>
                <td>{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td>{{ $cuota->tipo_cuota ?? 'Regular' }}</td>
                <td class="text-right">{{ number_format((float)$cuota->capital, $dec, ',', '.') }}</td>
                <td class="text-right">{{ number_format((float)$cuota->interes, $dec, ',', '.') }}</td>
                <td class="text-right" style="font-weight:bold">{{ number_format($totalFila, $dec, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="cuotas-footer">
                <td colspan="3" style="text-align:right; text-transform:uppercase; letter-spacing:0.3px;">TOTALES</td>
                <td class="text-right">{{ $sym }} {{ number_format($totalCapital, $dec, ',', '.') }}</td>
                <td class="text-right">{{ $sym }} {{ number_format($totalInteres, $dec, ',', '.') }}</td>
                <td class="text-right">{{ $sym }} {{ number_format($totalCuota, $dec, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <table class="signature-table">
        <tr>
            <td style="width: 50%; padding: 40px;">
                <div class="signature-box" style="margin: 0 auto;">
                    Firma del Cliente<br>
                    {{ $cliente->razon_social }}
                </div>
            </td>
            <td style="width: 50%; padding: 40px;">
                <div class="signature-box" style="margin: 0 auto;">
                    Firma Autorizada<br>
                    {{ $empresa->nombre_empresa ?? 'ERP CAMIONES' }}
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 8px; color: #9ca3af; text-align: center;">
        Esta nota de venta no es válida como factura legal según las normativas vigentes de la SET.
        Documento generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>