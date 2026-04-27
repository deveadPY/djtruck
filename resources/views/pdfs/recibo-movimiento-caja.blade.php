<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
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
        
        .watermark {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px; color: rgba(153, 27, 27, 0.02); z-index: -1; font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="watermark">COMPROBANTE DE CAJA</div>

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
                <div class="doc-label">Comprobante de Caja</div>
                <div class="doc-number">N°&nbsp;{{ str_pad((string)$movimiento->id, 8, '0', STR_PAD_LEFT) }}</div>
                <div class="company-info">Fecha: {{ \Carbon\Carbon::parse($movimiento->created_at)->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                <div class="section-title">DETALLES DE LA CAJA</div>
                <table class="info-table">
                    <tr><td class="label">Caja:</td><td class="value">{{ $caja->nombre }}</td></tr>
                    <tr><td class="label">Código:</td><td class="value">{{ $caja->codigo }}</td></tr>
                    <tr><td class="label">Responsable:</td><td class="value">{{ $usuario->name ?? 'Sistema' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="section-title">TIPO DE OPERACIÓN</div>
                <table class="info-table">
                    <tr><td class="label">Tipo:</td><td class="value">{{ $movimiento->tipo }}</td></tr>
                    <tr><td class="label">Concepto:</td><td class="value">{{ $movimiento->concepto }}</td></tr>
                    <tr><td class="label">Origen:</td><td class="value">{{ ucfirst($movimiento->ref_type ?? 'Manual') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">DETALLE DEL MOVIMIENTO</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción del Movimiento</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $movimiento->concepto }}</td>
                <td class="text-right">{{ $movimiento->moneda }} {{ number_format((float)$movimiento->monto, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals-table">
        <tr class="total-row">
            <td>TOTAL {{ $movimiento->tipo }}:</td>
            <td class="text-right">
                {{ $movimiento->moneda }} {{ number_format((float)$movimiento->monto, 2, ',', '.') }}
            </td>
        </tr>
        @if($movimiento->moneda !== 'USD' && $movimiento->monto_usd)
        <tr>
            <td style="font-size: 9px; color: #6b7280;">Equivalente en USD:</td>
            <td class="text-right" style="font-size: 9px; color: #6b7280;">
                $ {{ number_format((float)$movimiento->monto_usd, 2, ',', '.') }}
            </td>
        </tr>
        @endif
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%; padding: 40px;">
                <div class="signature-box" style="margin: 0 auto;">
                    Firma del Responsable<br>
                    {{ $usuario->name ?? 'Responsable de Caja' }}
                </div>
            </td>
            <td style="width: 50%; padding: 40px;">
                <div class="signature-box" style="margin: 0 auto;">
                    Firma y Sello Empresa<br>
                    {{ $empresa?->nombre_empresa ?? 'DJ TRUCKS & CARS' }}
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 8px; color: #9ca3af; text-align: center;">
        Este documento es un comprobante interno de caja.
        Generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>

</body>
</html>
