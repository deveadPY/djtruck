<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KuDE - {{ $venta->cdc_sifen }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8pt; color: #333; line-height: 1.2; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .logo { max-width: 150px; max-height: 80px; }
        .title-box { border: 1px solid #000; padding: 10px; text-align: center; background: #f5f5f5; }
        .title-box h1 { margin: 0; font-size: 11pt; text-transform: uppercase; }
        .title-box p { margin: 2px 0; font-weight: bold; }
        
        .section-title { background: #eee; padding: 3px 5px; font-weight: bold; border-bottom: 1px solid #000; margin-top: 10px; text-transform: uppercase; font-size: 7pt; }
        .data-grid { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .data-grid td { padding: 2px 0; }
        .label { font-weight: bold; color: #555; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { background: #000; color: #fff; padding: 5px; text-align: left; text-transform: uppercase; font-size: 7pt; }
        .items-table td { border-bottom: 1px solid #eee; padding: 5px; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-table { width: 35%; margin-left: auto; border-collapse: collapse; margin-top: 10px; }
        .totals-table td { padding: 3px 5px; border-bottom: 1px solid #eee; }
        .totals-table .grand-total { background: #000; color: #fff; font-weight: bold; font-size: 10pt; }

        .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 7pt; color: #777; }
        .cdc-box { word-break: break-all; font-family: 'Courier', monospace; font-size: 8pt; background: #f9f9f9; padding: 5px; border: 1px dashed #ccc; margin-top: 5px; }
        
        .qr-section { width: 100%; margin-top: 15px; }
        .qr-box { text-align: center; }
        .qr-image { width: 100px; height: 100px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if(isset($logo_base64) && $logo_base64)
                    <img src="data:image/png;base64,{{ $logo_base64 }}" class="logo">
                @endif
                <div style="margin-top: 10px;">
                    <strong style="font-size: 10pt;">{{ $emisor['razon_social'] }}</strong><br>
                    {{ $emisor['nombre_fantasia'] }}<br>
                    {{ $emisor['direccion'] }}<br>
                    Tel: {{ $emisor['telefono'] }} | Email: {{ $emisor['email'] }}
                </div>
            </td>
            <td style="width: 40%;">
                <div class="title-box">
                    <p>RUC: {{ $emisor['ruc'] }}</p>
                    <h1>{{ $tipo_desc }} ELECTRÓNICA</h1>
                    <p>TIMBRADO: {{ $emisor['timbrado'] }}</p>
                    <p>NÚMERO: {{ $nro_doc }}</p>
                    <p style="font-size: 7pt; font-weight: normal;">Establecimiento: {{ $emisor['establecimiento'] }} | Punto Exp: {{ $emisor['punto'] }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Información del Receptor</div>
    <table class="data-grid">
        <tr>
            <td style="width: 15%;" class="label">Razón Social:</td>
            <td style="width: 50%;">{{ optional($venta->cliente)->razon_social ?? 'CONSUMIDOR FINAL' }}</td>
            <td style="width: 15%;" class="label">Fecha Emisión:</td>
            <td style="width: 20%;">{{ optional($venta->fecha_emision_fe)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">RUC / CI:</td>
            <td>{{ optional($venta->cliente)->ruc ?? '44444401-7' }}</td>
            <td class="label">Condición:</td>
            <td>{{ $venta->detallesPago->first()?->tipo_pago ?? 'CONTADO' }}</td>
        </tr>
        <tr>
            <td class="label">Dirección:</td>
            <td colspan="3">{{ optional($venta->cliente)->direccion ?? '—' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">Código</th>
                <th style="width: 45%;">Descripción / Concepto</th>
                <th style="width: 10%;" class="text-center">Cant.</th>
                <th style="width: 15%;" class="text-right">Precio Unit.</th>
                <th style="width: 20%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            {{-- Aquí asumimos que las ventas tienen items, si no, mostramos el concepto general del vehículo --}}
            @if(isset($venta->items) && $venta->items->count() > 0)
                @foreach($venta->items as $item)
                    <tr>
                        <td class="text-center">{{ $item->id }}</td>
                        <td>{{ $item->descripcion }}</td>
                        <td class="text-center">{{ $item->cantidad }}</td>
                        <td class="text-right">{{ number_format($item->precio_unitario_moneda, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($item->subtotal_moneda, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="text-center">VH-{{ optional($venta->vehiculo)->id ?? 'N/A' }}</td>
                    <td>
                        <strong>{{ optional($venta->vehiculo)->marca ?? 'CONCEPTO' }} {{ optional($venta->vehiculo)->modelo ?? 'GENERAL' }}</strong><br>
                        Chasis: {{ optional($venta->vehiculo)->chasis ?? '—' }} | Color: {{ optional($venta->vehiculo)->color ?? '—' }}
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right">{{ number_format($venta->precio_venta_moneda, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($venta->precio_venta_moneda, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal IVA 10%:</td>
            <td class="text-right">{{ number_format($venta->precio_venta_moneda, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Liquidación IVA 10%:</td>
            <td class="text-right">{{ number_format(round($venta->precio_venta_moneda / 11), 0, ',', '.') }}</td>
        </tr>
        <tr class="grand-total">
            <td style="color: #fff;">TOTAL GS:</td>
            <td class="text-right" style="color: #fff;">{{ number_format($venta->precio_venta_moneda, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="qr-section">
        <table style="width: 100%;">
            <tr>
                <td style="width: 75%;">
                    <div class="label">Clave de Acceso (CDC):</div>
                    <div class="cdc-box">{{ $venta->cdc_sifen }}</div>
                    
                    <div style="margin-top: 10px;">
                        <span class="label">Protocolo SIFEN:</span> {{ $venta->sifen_numero_lote }}<br>
                        <span class="label">Ambiente:</span> {{ config('sifen.ambiente') == 'produccion' ? 'PRODUCCIÓN' : 'TESTING / SANDBOX' }}
                    </div>
                </td>
                <td style="width: 25%;" class="qr-box">
                    @if(isset($qr_base64))
                        <img src="data:image/svg+xml;base64,{{ $qr_base64 }}" class="qr-image">
                        <div style="font-size: 6pt; margin-top: 5px;">Consulte la validez de este documento escaneando el código QR.</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        ESTA ES UNA REPRESENTACIÓN GRÁFICA DE UN DOCUMENTO ELECTRÓNICO (KuDE) GENERADA POR EL SISTEMA DE GESTIÓN ERP CAMIONES.<br>
        EL DOCUMENTO ORIGINAL EN FORMATO XML HA SIDO FIRMADO DIGITALMENTE Y TRANSMITIDO A LA SUBSECRETARÍA DE ESTADO DE TRIBUTACIÓN (SET).
    </div>

</body>
</html>
