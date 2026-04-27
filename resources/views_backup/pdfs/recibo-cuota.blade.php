<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

    .header { border-bottom: 3px solid #6c63ff; padding-bottom: 14px; margin-bottom: 16px; }
    .header-top { display: table; width: 100%; }
    .header-empresa { display: table-cell; vertical-align: top; }
    .header-doc { display: table-cell; text-align: right; vertical-align: top; }
    .empresa-nombre { font-size: 17px; font-weight: bold; color: #6c63ff; }
    .empresa-sub { font-size: 9px; color: #555; margin-top: 2px; }
    .doc-titulo { font-size: 14px; font-weight: bold; color: #333; }
    .doc-numero { font-size: 11px; color: #555; margin-top: 4px; }
    .doc-fecha { font-size: 9px; color: #888; margin-top: 2px; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; }
    .badge-pagada { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .badge-mora    { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

    .section { margin-bottom: 14px; }
    .section-title { font-size: 9px; font-weight: bold; color: #6c63ff; text-transform: uppercase;
                     letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 8px; }

    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 3px 4px; vertical-align: top; }
    table.info td:first-child { color: #555; width: 38%; }
    table.info td:last-child { font-weight: bold; }

    table.detalle { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.detalle thead tr { background: #6c63ff; color: #fff; }
    table.detalle thead th { padding: 5px 8px; text-align: left; font-size: 10px; }
    table.detalle tbody tr { border-bottom: 1px solid #f0f0f0; }
    table.detalle tbody tr:nth-child(even) { background: #f8f7ff; }
    table.detalle tbody td { padding: 5px 8px; }

    .totales { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 14px; margin-top: 12px; }
    .totales table { width: 100%; border-collapse: collapse; }
    .totales td { padding: 3px 4px; }
    .totales td:last-child { text-align: right; font-weight: bold; }
    .total-final { font-size: 13px; color: #6c63ff; border-top: 1px solid #d1d5db; padding-top: 6px; margin-top: 4px; }

    .footer { border-top: 1px solid #e5e7eb; margin-top: 18px; padding-top: 10px; }
    .footer-cols { display: table; width: 100%; }
    .footer-col { display: table-cell; width: 50%; vertical-align: top; }
    .firma-box { border-top: 1px solid #6c63ff; margin-top: 36px; text-align: center; font-size: 9px; color: #555; padding-top: 4px; }
    .nota { font-size: 9px; color: #888; font-style: italic; margin-top: 10px; text-align: center; }

    .estado-row { display: table; width: 100%; margin-bottom: 14px; }
    .estado-left  { display: table-cell; vertical-align: middle; }
    .estado-right { display: table-cell; text-align: right; vertical-align: middle; }
</style>
</head>
<body>

{{-- ENCABEZADO --}}
<div class="header">
    <div class="header-top">
        <div class="header-empresa">
            @if($empresa?->logo_path)
            <img src="{{ public_path('storage/' . $empresa->logo_path) }}" alt="Logo" style="max-height:50px;max-width:120px;margin-bottom:4px;display:block;">
            @endif
            <div class="empresa-nombre">{{ $empresa?->nombre_empresa ?? config('erp.app_name') }}</div>
            @if($empresa?->ruc)<div class="empresa-sub">RUC: {{ $empresa->ruc }}</div>@endif
            @if($empresa?->telefono)<div class="empresa-sub">Tel: {{ $empresa->telefono }}</div>@endif
            @if($empresa?->email)<div class="empresa-sub">{{ $empresa->email }}</div>@endif
        </div>
        <div class="header-doc">
            <div class="doc-titulo">RECIBO DE PAGO DE CUOTA</div>
            <div class="doc-numero">N° {{ str_pad($cuota->id, 8, '0', STR_PAD_LEFT) }}</div>
            <div class="doc-fecha">Emitido: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>

{{-- ESTADO --}}
<div class="estado-row">
    <div class="estado-left">
        <strong>Estado del pago:</strong>
    </div>
    <div class="estado-right">
        @if($cuota->estado === 'PAGADA')
            <span class="badge badge-pagada">✓ PAGADA</span>
        @else
            <span class="badge badge-mora">{{ strtoupper($cuota->estado) }}</span>
        @endif
    </div>
</div>

{{-- CLIENTE --}}
<div class="section">
    <div class="section-title">Datos del Cliente</div>
    <table class="info">
        <tr>
            <td>Cliente:</td>
            <td>{{ $cliente->razon_social }}</td>
        </tr>
        @if($cliente->ruc)
        <tr>
            <td>RUC:</td>
            <td>{{ $cliente->ruc }}</td>
        </tr>
        @endif
        @if($cliente->email)
        <tr>
            <td>Email:</td>
            <td>{{ $cliente->email }}</td>
        </tr>
        @endif
        @if($cliente->telefono)
        <tr>
            <td>Teléfono:</td>
            <td>{{ $cliente->telefono }}</td>
        </tr>
        @endif
    </table>
</div>

{{-- VENTA --}}
<div class="section">
    <div class="section-title">Datos de la Venta</div>
    <table class="info">
        <tr>
            <td>N° Venta:</td>
            <td>{{ $venta->numero_venta }}</td>
        </tr>
        <tr>
            <td>Vehículo:</td>
            <td>{{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->año }})</td>
        </tr>
        <tr>
            <td>Chasis:</td>
            <td>{{ $vehiculo->numero_chasis }}</td>
        </tr>
        <tr>
            <td>Fecha de venta:</td>
            <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
        </tr>
    </table>
</div>

{{-- DETALLE DE LA CUOTA --}}
<div class="section">
    <div class="section-title">Detalle de la Cuota</div>
    <table class="detalle">
        <thead>
            <tr>
                <th>Cuota N°</th>
                <th>Vencimiento</th>
                <th>Capital</th>
                <th>Interés</th>
                @if(floatval($cuota->interes_mora) > 0)
                <th>Mora</th>
                @endif
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $cuota->numero_cuota }} / {{ $cuota->total_cuotas }}</td>
                <td>{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td>{{ $cuota->moneda }} {{ number_format($cuota->capital, 2) }}</td>
                <td>{{ $cuota->moneda }} {{ number_format($cuota->interes, 2) }}</td>
                @if(floatval($cuota->interes_mora) > 0)
                <td style="color:#dc2626;">{{ $cuota->moneda }} {{ number_format($cuota->interes_mora, 2) }}</td>
                @endif
                <td><strong>{{ $cuota->moneda }} {{ number_format(floatval($cuota->capital) + floatval($cuota->interes) + floatval($cuota->interes_mora), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
</div>

{{-- TOTALES --}}
<div class="totales">
    <table>
        <tr>
            <td>Capital:</td>
            <td>{{ $cuota->moneda }} {{ number_format($cuota->capital, 2) }}</td>
        </tr>
        <tr>
            <td>Interés:</td>
            <td>{{ $cuota->moneda }} {{ number_format($cuota->interes, 2) }}</td>
        </tr>
        @if(floatval($cuota->interes_mora) > 0)
        <tr>
            <td style="color:#dc2626;">Interés por mora:</td>
            <td style="color:#dc2626;">{{ $cuota->moneda }} {{ number_format($cuota->interes_mora, 2) }}</td>
        </tr>
        @endif
        <tr class="total-final">
            <td><strong>MONTO PAGADO:</strong></td>
            <td><strong>{{ $cuota->moneda }} {{ number_format($cuota->monto_pagado, 2) }}</strong></td>
        </tr>
    </table>
</div>

{{-- FECHA DE PAGO --}}
@if($cuota->fecha_pago_efectivo)
<div style="margin-top:10px; padding: 8px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:4px;">
    <strong>Fecha de pago efectivo:</strong> {{ \Carbon\Carbon::parse($cuota->fecha_pago_efectivo)->format('d/m/Y') }}
</div>
@endif

{{-- FOOTER / FIRMAS --}}
<div class="footer">
    <div class="footer-cols">
        <div class="footer-col">
            <div class="firma-box">Firma del Cliente</div>
        </div>
        <div class="footer-col" style="padding-left: 30px;">
            <div class="firma-box">Firma y Sello Empresa</div>
        </div>
    </div>
    <div class="nota">
        Este documento es un comprobante de pago válido. Conserve para sus registros.<br>
        Generado el {{ now()->format('d/m/Y') }} — {{ $empresa?->nombre_empresa ?? config('erp.app_name') }}
    </div>
</div>

</body>
</html>
