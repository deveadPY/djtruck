<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Cuota N° {{ str_pad((string)$cuota->id, 8, '0', STR_PAD_LEFT) }}</title>
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
        
        .payment-info { margin-top: 20px; font-size: 8.5px; }
        .payment-info table { width: 100%; border-collapse: collapse; }
        .payment-info th { background: #f9fafb; padding: 8px; text-align: left; border: 1px solid #e5e7eb; font-weight: bold; color: #6b7280; }
        .payment-info td { padding: 6px 8px; border: 1px solid #e5e7eb; color: #4b5563; }
        
        .watermark {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px; color: rgba(153, 27, 27, 0.02); z-index: -1; font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="watermark">RECIBO DE PAGO</div>

    <table class="header">
        <tr>
            <td style="width: 150px;">
                @if($empresa?->logo_path)
                    <img src="{{ $empresa->logoAbsPath() }}" alt="Logo" style="max-height: 80px; max-width: 140px;">
                @else
                    <div style="width: 70px; height: 70px; background: #374151; color: #fff; text-align: center; line-height: 70px; font-weight: bold; font-size: 20px; border-radius: 4px;">DJ</div>
                @endif
            </td>
            <td>
                <div class="company-name">{{ $empresa?->nombre_empresa ?? 'DJ TRUCKS & CARS' }}</div>
                <div class="company-info">
                    @if($empresa?->ruc) RUC: {{ $empresa->ruc }}<br> @endif
                    @if($empresa?->direccion) {{ $empresa->direccion }}<br> @endif
                    @if($empresa?->telefono) Tel: {{ $empresa->telefono }} @endif
                </div>
            </td>
            <td class="doc-title">
                <div class="doc-label">Recibo de Cuota</div>
                <div class="doc-number">N°&nbsp;{{ str_pad((string)$cuota->id, 8, '0', STR_PAD_LEFT) }}</div>
                <div class="company-info">Fecha Pago: {{ \Carbon\Carbon::parse($cuota->fecha_pago_efectivo)->format('d/m/Y') }}</div>
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
                    <tr><td class="label">Teléfono:</td><td class="value">{{ $cliente->telefono ?? 'N/A' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="section-title">REFERENCIA DE VENTA</div>
                <table class="info-table">
                    <tr><td class="label">Venta N°:</td><td class="value">{{ $venta->numero_venta }}</td></tr>
                    <tr><td class="label">Vehículo:</td><td class="value">{{ $vehiculo ? ($vehiculo->marca . ' ' . $vehiculo->modelo) : 'N/A' }}</td></tr>
                    <tr><td class="label">Chasis:</td><td class="value">{{ $vehiculo?->numero_chasis ?? 'N/A' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">DETALLE DEL PAGO</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pago de Cuota N° {{ $cuota->numero_cuota }} de {{ $cuota->total_cuotas }} (Capital)</td>
                <td class="text-right">{{ $venta->moneda_venta }} {{ number_format((float)$cuota->capital, 2, ',', '.') }}</td>
            </tr>
            @if($cuota->interes > 0)
            <tr>
                <td>Intereses del Periodo</td>
                <td class="text-right">{{ $venta->moneda_venta }} {{ number_format((float)$cuota->interes, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($cuota->interes_mora > 0)
            <tr>
                <td>Intereses por Mora</td>
                <td class="text-right">{{ $venta->moneda_venta }} {{ number_format((float)$cuota->interes_mora, 2, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr class="total-row">
            <td>TOTAL PAGADO:</td>
            <td class="text-right">
                {{ $venta->moneda_venta }} {{ number_format((float)$cuota->monto_pagado, 2, ',', '.') }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; font-size: 9px; color: #6b7280; font-style: italic;">
        Observaciones: Pago correspondiente al plan de cuotas {{ $plan->tipo_plan }}.
    </div>

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
                    {{ $empresa?->nombre_empresa ?? 'DJ TRUCKS & CARS' }}
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 8px; color: #9ca3af; text-align: center;">
        Este recibo es un comprobante de pago interno y no sustituye a la factura legal.
        Documento generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>