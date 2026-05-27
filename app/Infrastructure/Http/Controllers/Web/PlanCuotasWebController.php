<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Domain\Finance\Services\CajaService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Http\Requests\PayInstallmentRequest;
use App\Infrastructure\Settings\EmpresaSettings;
use Barryvdh\DomPDF\Facade\Pdf;

class PlanCuotasWebController extends Controller
{
    public function __construct(
        private readonly CajaService $cajaService,
        private readonly \App\Application\Installments\PayInstallmentsUseCase $payUseCase,
        private readonly \App\Application\Installments\LiquidatePlanUseCase $liquidateUseCase,
    ) {}

    public function create($ventaId)
    {
        $venta = DB::table('ventas')->where('id', $ventaId)->whereNull('deleted_at')->firstOrFail();
        $venta->vehiculo = DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first();
        $venta->cliente = DB::table('clientes')->where('id', $venta->cliente_id)->first();
        $vehiculos_canje = DB::table('vehiculos')->whereIn('estado', ['DISPONIBLE', 'EN_PREPARACION', 'TOMA'])->whereNull('deleted_at')->get();
        return view('planes_cuotas.create', compact('venta', 'vehiculos_canje'));
    }

    public function store(Request $request, $ventaId)
    {
        $data = $request->validate([
            'tipo_plan' => 'required|in:FRANCESA,MANUAL',
            'moneda' => 'required|string|max:3',
            'capital_total' => 'required|numeric|min:0',
            'capital_total_usd' => 'required|numeric|min:0',
            'numero_cuotas' => 'nullable|integer|min:1|max:120',
            'tasa_interes_mensual' => 'nullable|numeric|min:0',
            'fecha_primera_cuota' => 'nullable|date',
            'refuerzo_cada' => 'nullable|integer|min:0',
            'refuerzo_monto' => 'nullable|numeric|min:0',
        ]);

        $venta = DB::table('ventas')->where('id', $ventaId)->firstOrFail();

        // ── 1. Registrar las ENTREGAS como detalles_pago ──────
        $entregas = $request->input('entregas', []);
        foreach ($entregas as $entrega) {
            $montoUsd = floatval($entrega['monto_usd'] ?? 0);
            if ($montoUsd <= 0)
                continue;

            DB::table('detalles_pago')->insert([
                'venta_id' => $ventaId,
                'tipo_pago' => $entrega['tipo'] ?? 'EFECTIVO',
                'moneda' => 'USD',
                'monto_moneda' => $montoUsd,
                'monto_usd' => $montoUsd,
                'tasa_cambio' => 1,
                'referencia_bancaria' => $entrega['referencia'] ?? null,
                'fecha_pago' => $entrega['fecha'] ?? now()->toDateString(),
                'observaciones' => $entrega['tipo'] === 'VEHICULO_CANJE' ? 'Vehículo en parte de pago' : null,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ── Registrar ingreso en Caja Capital para pagos en efectivo ──
            $tipoPagoEntrega = $entrega['tipo'] ?? 'EFECTIVO';
            if (in_array($tipoPagoEntrega, ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'TARJETA'])) {
                $tipoLabel = match ($tipoPagoEntrega) {
                    'EFECTIVO'      => 'Efectivo',
                    'TRANSFERENCIA' => 'Transferencia',
                    'CHEQUE'        => 'Cheque',
                    'TARJETA'       => 'Tarjeta',
                    default         => $tipoPagoEntrega,
                };
                try {
                    $this->cajaService->ingresoCapital(
                        "Entrega inicial plan cuotas – {$tipoLabel} (Venta ID {$ventaId})",
                        'USD',
                        $montoUsd,
                        $montoUsd,
                        $ventaId,
                        'venta'
                    );
                } catch (\RuntimeException $e) {
                    Log::warning('PlanCuotasWebController store: no se pudo registrar movimiento en caja: ' . $e->getMessage());
                }
            }
        }

        // ── 2. Crear el Plan de Cuotas ────────────────────────
        $tipoPlan = $data['tipo_plan'];
        $data['venta_id'] = $ventaId;
        $data['cliente_id'] = $venta->cliente_id;
        $data['estado'] = 'ACTIVO';
        $data['created_by'] = Auth::id();
        $data['tasa_interes_mensual'] = $data['tasa_interes_mensual'] ?? 0;

        // For manual plans, count the cuotas from the submitted array
        $cuotasManual = $request->input('cuotas_manual', []);
        if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
            $data['numero_cuotas'] = count($cuotasManual);
        }
        $data['numero_cuotas'] = $data['numero_cuotas'] ?? 12;
        $data['fecha_primera_cuota'] = $data['fecha_primera_cuota'] ?? now()->addMonth()->toDateString();

        // Remove refuerzo fields before inserting (not in DB schema)
        unset($data['refuerzo_cada'], $data['refuerzo_monto']);

        $planId = DB::table('planes_cuotas')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

        // ── 3. Generar las CUOTAS ─────────────────────────────
        if ($tipoPlan === 'MANUAL' && count($cuotasManual) > 0) {
            $this->generarCuotasManuales($planId, $ventaId, $data, $cuotasManual);
        } else {
            $this->generarCuotasAutomaticas($planId, $ventaId, $data, $request);
        }

        // Register the plan as a detalle_pago of type PLAN_CUOTAS
        DB::table('detalles_pago')->insert([
            'venta_id' => $ventaId,
            'tipo_pago' => 'PLAN_CUOTAS',
            'moneda' => $data['moneda'],
            'monto_moneda' => $data['capital_total'],
            'monto_usd' => $data['capital_total_usd'],
            'tasa_cambio' => 1,
            'plan_cuotas_id' => $planId,
            'fecha_pago' => now()->toDateString(),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('ventas.show', $ventaId)->with('success', 'Plan de pagos creado exitosamente.');
    }

    /**
     * Generate cuotas from the manual grid submitted by user.
     */
    private function generarCuotasManuales(int $planId, int $ventaId, array $plan, array $cuotasManual): void
    {
        $cuotas = [];
        $i = 1;
        $totalCuotas = count($cuotasManual);

        foreach ($cuotasManual as $row) {
            $monto = floatval($row['monto'] ?? 0);
            if ($monto <= 0)
                continue;

            $cuotas[] = [
                'plan_cuotas_id' => $planId,
                'venta_id' => $ventaId,
                'numero_cuota' => $i,
                'total_cuotas' => $totalCuotas,
                'tipo_plan' => 'MANUAL',
                'moneda' => $plan['moneda'],
                'capital' => round($monto, 4),
                'interes' => 0,
                'fecha_vencimiento' => $row['fecha'] ?? now()->addMonths($i)->toDateString(),
                'estado' => 'PENDIENTE',
                'monto_pagado' => 0,
                'interes_mora' => 0,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $i++;
        }

        if (count($cuotas) > 0) {
            DB::table('cuotas')->insert($cuotas);
        }
    }

    /**
     * Generate cuotas automatically (Francesa, Alemana, or Manual uniform).
     * Also supports reinforcement payments every N months.
     */
    private function generarCuotasAutomaticas(int $planId, int $ventaId, array $plan, Request $request): void
    {
        $capital = $plan['capital_total'];
        $n = $plan['numero_cuotas'];
        $tasa = ($plan['tasa_interes_mensual'] ?? 0) / 100;
        $tipo = $plan['tipo_plan'];
        $moneda = $plan['moneda'];
        $fecha = \Carbon\Carbon::parse($plan['fecha_primera_cuota']);

        $refuerzoCada = intval($request->input('refuerzo_cada', 0));
        $refuerzoMonto = floatval($request->input('refuerzo_monto', 0));

        // If there are reinforcements, reduce the capital proportionally
        $numRefuerzos = 0;
        if ($refuerzoCada > 0 && $refuerzoMonto > 0) {
            $numRefuerzos = intdiv($n, $refuerzoCada);
            $capital -= ($numRefuerzos * $refuerzoMonto);
        }

        $cuotas = [];
        $cuotaNumero = 0;
        $capitalRestante = $capital;

        for ($i = 1; $i <= $n; $i++) {
            $cuotaNumero++;
            $fechaCuota = $fecha->copy()->addMonths($i - 1)->toDateString();

            if ($tipo === 'FRANCESA') {
                $cuotaTotal = $tasa > 0
                    ? $capital * $tasa / (1 - pow(1 + $tasa, -$n))
                    : $capital / $n;
                $interes = $capitalRestante * $tasa;
                $cap = $cuotaTotal - $interes;
            } elseif ($tipo === 'ALEMANA') {
                $cap = $capital / $n;
                $interes = $capitalRestante * $tasa;
            } else {
                $cap = $capital / $n;
                $interes = 0;
            }

            $cuotas[] = [
                'plan_cuotas_id' => $planId,
                'venta_id' => $ventaId,
                'numero_cuota' => $cuotaNumero,
                'total_cuotas' => $n + $numRefuerzos,
                'tipo_plan' => $tipo,
                'moneda' => $moneda,
                'capital' => round($cap, 4),
                'interes' => round($interes, 4),
                'fecha_vencimiento' => $fechaCuota,
                'estado' => 'PENDIENTE',
                'monto_pagado' => 0,
                'interes_mora' => 0,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($tipo === 'ALEMANA') {
                $capitalRestante -= $cap;
            }

            // Insert reinforcement cuota after every N months
            if ($refuerzoCada > 0 && $refuerzoMonto > 0 && $i % $refuerzoCada === 0) {
                $cuotaNumero++;
                $cuotas[] = [
                    'plan_cuotas_id' => $planId,
                    'venta_id' => $ventaId,
                    'numero_cuota' => $cuotaNumero,
                    'total_cuotas' => $n + $numRefuerzos,
                    'tipo_plan' => $tipo,
                    'moneda' => $moneda,
                    'capital' => round($refuerzoMonto, 4),
                    'interes' => 0,
                    'fecha_vencimiento' => $fechaCuota,
                    'estado' => 'PENDIENTE',
                    'monto_pagado' => 0,
                    'interes_mora' => 0,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Update total_cuotas with actual final count
        $totalFinal = count($cuotas);
        foreach ($cuotas as &$c) {
            $c['total_cuotas'] = $totalFinal;
        }

        DB::table('cuotas')->insert($cuotas);

        // Update plan with actual numero_cuotas
        DB::table('planes_cuotas')->where('id', $planId)->update(['numero_cuotas' => $totalFinal]);
    }

    public function show($planId)
    {
        $plan = DB::table('planes_cuotas')->where('id', $planId)->firstOrFail();
        $cuotas = DB::table('cuotas')->where('plan_cuotas_id', $planId)->whereNull('deleted_at')->orderBy('numero_cuota')->get();
        $venta = DB::table('ventas')->where('id', $plan->venta_id)->first();
        $cliente = DB::table('clientes')->where('id', $plan->cliente_id)->first();

        $pagado = $cuotas->where('estado', 'PAGADA')->count();
        $pendiente = $cuotas->where('estado', 'PENDIENTE')->count();
        $vencidas = $cuotas->where('estado', 'VENCIDA')->count();

        // También cargar las entregas
        $entregas = DB::table('detalles_pago')
            ->where('venta_id', $plan->venta_id)
            ->where('tipo_pago', '!=', 'PLAN_CUOTAS')
            ->whereNull('deleted_at')
            ->get();

        return view('planes_cuotas.show', compact('plan', 'cuotas', 'venta', 'cliente', 'pagado', 'pendiente', 'vencidas', 'entregas'));
    }

    public function downloadRecibo($cuotaId)
    {
        $cuota = DB::table('cuotas')->where('id', $cuotaId)->whereNull('deleted_at')->firstOrFail();

        if (!in_array($cuota->estado, ['PAGADA', 'PAGADA_PARCIAL'])) {
            return back()->withErrors(['error' => 'Solo se puede generar recibo de cuotas pagadas.']);
        }

        $plan     = DB::table('planes_cuotas')->where('id', $cuota->plan_cuotas_id)->firstOrFail();
        $venta    = DB::table('ventas')->where('id', $cuota->venta_id)->firstOrFail();
        $cliente  = DB::table('clientes')->where('id', $plan->cliente_id)->firstOrFail();
        $vehiculo = $venta->vehiculo_id
            ? DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first()
            : null;

        $empresa = EmpresaSettings::get();

        $pdf = Pdf::loadView('pdfs.recibo-cuota', compact('cuota', 'plan', 'venta', 'cliente', 'vehiculo', 'empresa'))
            ->setPaper('a5', 'portrait');

        // Autoprint Script
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $canvas->get_cpdf()->openObject();
        $canvas->get_cpdf()->addJavascript("print(true);");
        $canvas->get_cpdf()->closeObject();

        return $pdf->stream("recibo-cuota-{$cuotaId}.pdf");
    }

    public function pagarCuota(PayInstallmentRequest $request, $cuotaId)
    {
        $dto = \App\Application\Installments\PayInstallmentsDTO::fromArray([
            'cuotas_ids'                 => [(int) $cuotaId],
            'monto_pagado'               => (float) $request->monto_pagado,
            'moneda'                     => 'USD',
            'fecha_pago'                 => $request->fecha_pago,
            'caja_id'                    => $request->caja_id,
            'aplicar_descuento_anticipo'  => (bool) ($request->aplicar_descuento_anticipo ?? false),
            'descuento_anticipo_pct'     => $request->descuento_anticipo_pct,
            'descuento_proporcional'     => (bool) ($request->descuento_proporcional ?? false),
            'observaciones'              => $request->observacion,
            'user_id'                    => Auth::id(),
            'ip_address'                 => $request->ip(),
        ]);

        try {
            $result = $this->payUseCase->execute($dto);

            $msg = "Cuota pagada correctamente. Recibo: {$result['numero_recibo']}";
            if ($result['descuento_aplicado'] > 0) {
                $msg .= ' (descuento por anticipo: $' . number_format($result['descuento_aplicado'], 2) . ')';
            }

            return back()->with('success', $msg)->with('show_print_cuota', $cuotaId);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Liquida el plan de cuotas completo.
     */
    public function liquidarPlan(Request $request, $planId)
    {
        $validated = $request->validate([
            'fecha_liquidacion'            => 'required|date',
            'caja_id'                      => 'nullable|integer|exists:cajas,id',
            'aplicar_descuento_liquidacion' => 'nullable|boolean',
            'descuento_liquidacion_pct'    => 'nullable|numeric|min:0|max:100',
            'observaciones'                => 'nullable|string|max:500',
        ]);

        $dto = \App\Application\Installments\LiquidatePlanDTO::fromArray([
            'plan_id'                      => (int) $planId,
            'fecha_liquidacion'            => $validated['fecha_liquidacion'],
            'caja_id'                      => $validated['caja_id'] ?? null,
            'aplicar_descuento_liquidacion' => (bool) ($validated['aplicar_descuento_liquidacion'] ?? false),
            'descuento_liquidacion_pct'    => $validated['descuento_liquidacion_pct'] ?? null,
            'observaciones'                => $validated['observaciones'] ?? null,
            'user_id'                      => Auth::id(),
            'ip_address'                   => $request->ip(),
        ]);

        try {
            $result = $this->liquidateUseCase->execute($dto);

            $msg = "Plan liquidado correctamente. Recibo: {$result['numero_recibo']}";
            $msg .= ' | Total: $' . number_format($result['total_liquidacion'], 2);
            if ($result['descuento_aplicado'] > 0) {
                $msg .= ' (ahorro: $' . number_format($result['descuento_aplicado'], 2) . ')';
            }

            return redirect()->route('planes_cuotas.show', $planId)
                ->with('success', $msg);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
