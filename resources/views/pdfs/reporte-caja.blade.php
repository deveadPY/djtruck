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
        .doc-number { font-size: 11px; font-weight: bold; color: #991b1b; margin-top: 2px; }

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
        .label { color: #6b7280; width: 110px; font-size: 8px; text-transform: uppercase; font-weight: 600; }
        .value { font-weight: 600; color: #111827; }

        /* Resumen cards */
        .resumen-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .resumen-table td { border: 1px solid #e5e7eb; padding: 10px; text-align: center; }
        .resumen-valor { font-size: 13px; font-weight: bold; }
        .resumen-label { font-size: 8px; color: #6b7280; margin-top: 2px; text-transform: uppercase; }
        .color-green { color: #16a34a; }
        .color-red { color: #dc2626; }
        .color-blue { color: #991b1b; }
        .color-orange { color: #d97706; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .items-table th {
            background: #f9fafb;
            color: #4b5563;
            padding: 5px 4px;
            text-align: left;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table td { padding: 4px; border-bottom: 1px solid #f3f4f6; font-size: 8px; color: #374151; }
        .items-table tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .badge { padding: 2px 6px; border-radius: 3px; font-size: 7px; font-weight: bold; white-space: nowrap; }
        .badge-ingreso { background: #d1fae5; color: #065f46; }
        .badge-egreso { background: #fee2e2; color: #991b1b; }

        .ref-badge { padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: 600; background: #f3f4f6; color: #4b5563; }

        /* Totals */
        .totals-table { width: 260px; margin-left: auto; margin-top: 15px; border-collapse: collapse; }
        .totals-table td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; font-size: 9px; }
        .total-row { font-size: 11px; font-weight: bold; color: #991b1b; background: #fef2f2; }
        .total-row td { padding: 10px 6px; border-top: 1px solid #fee2e2; border-bottom: 1px solid #fee2e2; }

        .footer { margin-top: 25px; font-size: 8px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }

        .watermark {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px; color: rgba(153, 27, 27, 0.02); z-index: -1; font-weight: bold;
        }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="watermark">REPORTE DE CAJA</div>

    {{-- HEADER --}}
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
                <div class="doc-label">Reporte de Caja</div>
                <div class="doc-number">{{ $caja->nombre }}</div>
                <div class="company-info" style="margin-top:4px;">
                    Per&iacute;odo: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
                </div>
                <div class="company-info">Emitido: {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    {{-- INFO CAJA --}}
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                <div class="section-title">Datos de la Caja</div>
                <table class="info-table">
                    <tr><td class="label">Caja:</td><td class="value">{{ $caja->nombre }}</td></tr>
                    <tr><td class="label">C&oacute;digo:</td><td class="value">{{ $caja->codigo }}</td></tr>
                    <tr><td class="label">Moneda principal:</td><td class="value">{{ $caja->moneda_principal }}</td></tr>
                    <tr><td class="label">Generado por:</td><td class="value">{{ $usuario->name ?? 'Sistema' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="section-title">Filtros Aplicados</div>
                <table class="info-table">
                    <tr><td class="label">Fecha desde:</td><td class="value">{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}</td></tr>
                    <tr><td class="label">Fecha hasta:</td><td class="value">{{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</td></tr>
                    <tr><td class="label">Tipo:</td><td class="value">{{ $tipoFil ?: 'Todos' }}</td></tr>
                    <tr><td class="label">Total registros:</td><td class="value">{{ $movimientos->count() }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- RESUMEN FINANCIERO --}}
    <div class="section-title">Resumen del Per&iacute;odo</div>
    <table class="resumen-table">
        <tr>
            <td style="width:25%;">
                <div class="resumen-valor color-green">$ {{ number_format($totales->ingresos_usd ?? 0, 2, ',', '.') }}</div>
                <div class="resumen-label">Total Ingresos</div>
            </td>
            <td style="width:25%;">
                <div class="resumen-valor color-red">$ {{ number_format($totales->egresos_usd ?? 0, 2, ',', '.') }}</div>
                <div class="resumen-label">Total Egresos</div>
            </td>
            @php $neto = ($totales->ingresos_usd ?? 0) - ($totales->egresos_usd ?? 0); @endphp
            <td style="width:25%;">
                <div class="resumen-valor {{ $neto >= 0 ? 'color-green' : 'color-red' }}">$ {{ number_format($neto, 2, ',', '.') }}</div>
                <div class="resumen-label">Neto Per&iacute;odo</div>
            </td>
            <td style="width:25%;">
                <div class="resumen-valor color-blue">$ {{ number_format($saldoActual['saldo_usd'] ?? 0, 2, ',', '.') }}</div>
                <div class="resumen-label">Saldo Actual</div>
            </td>
        </tr>
    </table>

    {{-- RESUMEN PYG si aplica --}}
    @if(($totales->ingresos_pyg ?? 0) > 0 || ($totales->egresos_pyg ?? 0) > 0)
    <table class="resumen-table" style="margin-top:-5px;">
        <tr>
            <td style="width:33%;">
                <div class="resumen-valor color-green" style="font-size:11px;">Gs {{ number_format($totales->ingresos_pyg ?? 0, 0, ',', '.') }}</div>
                <div class="resumen-label">Ingresos PYG</div>
            </td>
            <td style="width:33%;">
                <div class="resumen-valor color-red" style="font-size:11px;">Gs {{ number_format($totales->egresos_pyg ?? 0, 0, ',', '.') }}</div>
                <div class="resumen-label">Egresos PYG</div>
            </td>
            @php $netoPyg = ($totales->ingresos_pyg ?? 0) - ($totales->egresos_pyg ?? 0); @endphp
            <td style="width:34%;">
                <div class="resumen-valor {{ $netoPyg >= 0 ? 'color-green' : 'color-red' }}" style="font-size:11px;">Gs {{ number_format($netoPyg, 0, ',', '.') }}</div>
                <div class="resumen-label">Neto PYG</div>
            </td>
        </tr>
    </table>
    @endif

    {{-- DETALLE DE MOVIMIENTOS --}}
    <div class="section-title">Detalle de Movimientos ({{ $movimientos->count() }} registros)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:14%;">Fecha</th>
                <th style="width:34%;">Concepto</th>
                <th style="width:11%;">Origen</th>
                <th class="text-right" style="width:17%;">Monto</th>
                <th class="text-right" style="width:14%;">USD</th>
                <th class="text-center" style="width:10%;">Tipo</th>
            </tr>
        </thead>
        <tbody>
            @php $runningUsd = 0; @endphp
            @forelse($movimientos as $m)
                @php
                    if ($m->tipo === 'INGRESO') $runningUsd += (float) $m->monto_usd;
                    else $runningUsd -= (float) $m->monto_usd;

                    $origenLabel = match($m->ref_type) {
                        'venta'            => 'Venta',
                        'cuota'            => 'Cuota',
                        'factura_gasto_op' => 'Gasto local',
                        'factura_vehiculo' => 'Gasto veh.',
                        'factura_mixto'    => 'Mixto',
                        'transferencia'    => 'Transfer.',
                        'manual'           => 'Manual',
                        default            => $m->ref_type ?? '-',
                    };
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($m->concepto, 45) }}</td>
                    <td><span class="ref-badge">{{ $origenLabel }}</span></td>
                    <td class="text-right" style="font-family: DejaVu Sans Mono, monospace;">
                        {{ number_format((float)$m->monto, 2, ',', '.') }} {{ $m->moneda }}
                    </td>
                    <td class="text-right" style="font-family: DejaVu Sans Mono, monospace; font-weight:600; color:{{ $m->tipo === 'INGRESO' ? '#16a34a' : '#dc2626' }};">
                        {{ $m->tipo === 'INGRESO' ? '+' : '-' }} $ {{ number_format((float)$m->monto_usd, 2, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $m->tipo === 'INGRESO' ? 'badge-ingreso' : 'badge-egreso' }}">
                            {{ $m->tipo }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:20px; color:#9ca3af;">Sin movimientos en el per&iacute;odo seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- TOTALES --}}
    <table class="totals-table">
        <tr>
            <td class="label">Total Ingresos (USD):</td>
            <td class="text-right" style="color:#16a34a; font-weight:bold;">$ {{ number_format($totales->ingresos_usd ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Total Egresos (USD):</td>
            <td class="text-right" style="color:#dc2626; font-weight:bold;">$ {{ number_format($totales->egresos_usd ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td>NETO PER&Iacute;ODO:</td>
            <td class="text-right">$ {{ number_format($neto, 2, ',', '.') }}</td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer">
        Reporte de caja generado el {{ now()->format('d/m/Y H:i:s') }} | {{ $empresa?->nombre_empresa ?? 'DJ TRUCKS & CARS' }}<br>
        Este documento es un reporte interno. Los montos reflejan el estado al momento de su emisi&oacute;n.
    </div>
</body>
</html>
