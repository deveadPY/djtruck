<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

    .header { border-bottom: 3px solid #6c63ff; padding-bottom: 12px; margin-bottom: 14px; }
    .header-top { display: table; width: 100%; }
    .header-empresa { display: table-cell; vertical-align: top; }
    .header-doc { display: table-cell; text-align: right; vertical-align: top; }
    .empresa-nombre { font-size: 16px; font-weight: bold; color: #6c63ff; }
    .empresa-sub { font-size: 9px; color: #555; margin-top: 2px; }
    .doc-titulo { font-size: 13px; font-weight: bold; color: #333; }
    .doc-fecha { font-size: 9px; color: #888; margin-top: 4px; }

    .section { margin-bottom: 12px; }
    .section-title { font-size: 9px; font-weight: bold; color: #6c63ff; text-transform: uppercase;
                     letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 7px; }

    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 3px 4px; vertical-align: top; }
    table.info td:first-child { color: #555; width: 35%; }
    table.info td:last-child { font-weight: bold; }

    .resumen-grid { display: table; width: 100%; margin-bottom: 12px; border-collapse: collapse; }
    .resumen-card { display: table-cell; width: 25%; border: 1px solid #e5e7eb; border-radius: 4px;
                    padding: 10px; text-align: center; }
    .resumen-card + .resumen-card { border-left: none; }
    .resumen-valor { font-size: 14px; font-weight: bold; color: #6c63ff; }
    .resumen-label { font-size: 8px; color: #888; margin-top: 2px; }
    .resumen-card.danger .resumen-valor { color: #dc2626; }
    .resumen-card.success .resumen-valor { color: #16a34a; }
    .resumen-card.warning .resumen-valor { color: #d97706; }

    table.cuotas { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.cuotas thead tr { background: #6c63ff; color: #fff; }
    table.cuotas thead th { padding: 5px 6px; text-align: left; }
    table.cuotas tbody tr { border-bottom: 1px solid #f0f0f0; }
    table.cuotas tbody tr:nth-child(even) { background: #fafaf9; }
    table.cuotas tbody td { padding: 4px 6px; }
    table.cuotas tfoot tr { background: #f3f4f6; font-weight: bold; }
    table.cuotas tfoot td { padding: 5px 6px; border-top: 2px solid #6c63ff; }

    .badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
    .badge-pendiente { background: #fef3c7; color: #92400e; }
    .badge-pagada    { background: #d1fae5; color: #065f46; }
    .badge-vencida   { background: #fee2e2; color: #991b1b; }
    .badge-mora      { background: #ffe4e6; color: #9f1239; }
    .badge-parcial   { background: #e0f2fe; color: #075985; }
    .badge-anulada   { background: #f3f4f6; color: #6b7280; }

    .footer { border-top: 1px solid #e5e7eb; margin-top: 16px; padding-top: 8px; text-align: center; }
    .nota { font-size: 8px; color: #888; font-style: italic; margin-top: 6px; }

    .venta-header { background: #f0edff; padding: 6px 8px; border-left: 3px solid #6c63ff;
                    margin-bottom: 4px; margin-top: 8px; font-weight: bold; font-size: 9px; }
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
            <div class="doc-titulo">ESTADO DE CUENTA</div>
            <div class="doc-fecha">Emitido: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>

{{-- DATOS DEL CLIENTE --}}
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
        @if(floatval($cliente->linea_credito_usd ?? 0) > 0)
        <tr>
            <td>Línea de crédito:</td>
            <td>USD {{ number_format($cliente->linea_credito_usd, 2) }}</td>
        </tr>
        <tr>
            <td>Crédito disponible:</td>
            <td style="color:{{ $creditoDisponible < 0 ? '#dc2626' : '#16a34a' }}">
                USD {{ number_format($creditoDisponible, 2) }}
            </td>
        </tr>
        @endif
    </table>
</div>

{{-- RESUMEN --}}
<div class="section">
    <div class="section-title">Resumen</div>
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:25%; border:1px solid #e5e7eb; padding:10px; text-align:center;">
                <div style="font-size:13px; font-weight:bold; color:#6c63ff;">USD {{ number_format($totalDeuda, 2) }}</div>
                <div style="font-size:8px; color:#888; margin-top:2px;">DEUDA TOTAL</div>
            </td>
            <td style="width:25%; border:1px solid #e5e7eb; border-left:none; padding:10px; text-align:center;">
                <div style="font-size:13px; font-weight:bold; color:#16a34a;">USD {{ number_format($totalPagado, 2) }}</div>
                <div style="font-size:8px; color:#888; margin-top:2px;">TOTAL PAGADO</div>
            </td>
            <td style="width:25%; border:1px solid #e5e7eb; border-left:none; padding:10px; text-align:center;">
                <div style="font-size:13px; font-weight:bold; color:#d97706;">USD {{ number_format($saldoPendiente, 2) }}</div>
                <div style="font-size:8px; color:#888; margin-top:2px;">SALDO PENDIENTE</div>
            </td>
            <td style="width:25%; border:1px solid #e5e7eb; border-left:none; padding:10px; text-align:center;">
                <div style="font-size:13px; font-weight:bold; color:#dc2626;">USD {{ number_format($totalMora, 2) }}</div>
                <div style="font-size:8px; color:#888; margin-top:2px;">INTERÉS MORA</div>
            </td>
        </tr>
    </table>
</div>

{{-- CUOTAS POR VENTA --}}
<div class="section">
    <div class="section-title">Detalle de Cuotas por Venta</div>

    @forelse($ventasConPlan as $ventaData)
        <div class="venta-header">
            {{ $ventaData['venta']->numero_venta }} —
            {{ $ventaData['vehiculo']->marca }} {{ $ventaData['vehiculo']->modelo }}
            ({{ $ventaData['vehiculo']->año }}) | Chasis: {{ $ventaData['vehiculo']->numero_chasis }}
        </div>

        <table class="cuotas">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Vencimiento</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Mora</th>
                    <th>Monto Total</th>
                    <th>Pagado</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventaData['cuotas'] as $cuota)
                @php
                    $total  = floatval($cuota->capital) + floatval($cuota->interes) + floatval($cuota->interes_mora);
                    $saldo  = max(0, $total - floatval($cuota->monto_pagado));
                    $estados = [
                        'PENDIENTE'     => 'badge-pendiente',
                        'PAGADA'        => 'badge-pagada',
                        'VENCIDA'       => 'badge-vencida',
                        'EN_MORA'       => 'badge-mora',
                        'PAGADA_PARCIAL'=> 'badge-parcial',
                        'ANULADA'       => 'badge-anulada',
                    ];
                    $badgeClass = $estados[$cuota->estado] ?? 'badge-pendiente';
                @endphp
                <tr @if(in_array($cuota->estado, ['VENCIDA','EN_MORA'])) style="background:#fff5f5;" @endif>
                    <td>{{ $cuota->numero_cuota }}/{{ $cuota->total_cuotas }}</td>
                    <td>{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                    <td>{{ number_format($cuota->capital, 2) }}</td>
                    <td>{{ number_format($cuota->interes, 2) }}</td>
                    <td @if(floatval($cuota->interes_mora) > 0) style="color:#dc2626;" @endif>
                        {{ number_format($cuota->interes_mora, 2) }}
                    </td>
                    <td>{{ number_format($total, 2) }}</td>
                    <td style="color:#16a34a;">{{ number_format($cuota->monto_pagado, 2) }}</td>
                    <td @if($saldo > 0) style="color:#d97706;" @endif>{{ number_format($saldo, 2) }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $cuota->estado }}</span></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Subtotales ({{ $ventaData['venta']->moneda_venta }})</td>
                    <td>{{ number_format($ventaData['subtotal_total'], 2) }}</td>
                    <td>{{ number_format($ventaData['subtotal_pagado'], 2) }}</td>
                    <td>{{ number_format($ventaData['subtotal_saldo'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @empty
        <p style="color:#888; font-style:italic;">El cliente no tiene planes de pago activos.</p>
    @endforelse
</div>

{{-- FOOTER --}}
<div class="footer">
    <div class="nota">
        Estado de cuenta generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}<br>
        Este documento refleja el estado al momento de su emisión. — {{ $empresa?->nombre_empresa ?? config('erp.app_name') }}
    </div>
</div>

</body>
</html>
