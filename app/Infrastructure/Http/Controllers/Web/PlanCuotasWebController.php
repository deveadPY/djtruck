<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Http\Requests\PayInstallmentRequest;
use App\Infrastructure\Settings\EmpresaSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanCuotasWebController extends Controller
{
    public function __construct(
        private readonly CajaService          $cajaService,
        private readonly InstallmentGenerator $installmentGenerator,
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
            'tipo_plan' => 'required|in:FRANCESA,ALEMANA,MANUAL',
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
            $this->installmentGenerator->generateManual(
                $planId, $ventaId, $data['moneda'], $cuotasManual
            );
        } else {
            $moneda  = Currency::from($data['moneda']);
            $capital = new Money((float) $data['capital_total'], $moneda);
            $this->installmentGenerator->generate(
                $planId,
                $ventaId,
                InstallmentPlan::from($tipoPlan),
                $capital,
                (int)   ($data['numero_cuotas'] ?? 12),
                (float) ($data['tasa_interes_mensual'] ?? 0),
                $data['fecha_primera_cuota'] ?? now()->addMonth()->toDateString(),
                (int)   $request->input('refuerzo_cada', 0),
                (float) $request->input('refuerzo_monto', 0),
            );
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
        $vehiculo = DB::table('vehiculos')->where('id', $venta->vehiculo_id)->firstOrFail();

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
        // ── Guard: evitar doble pago ─────────────────────────────────────────
        $cuota = DB::table('cuotas')->where('id', $cuotaId)->whereNull('deleted_at')->firstOrFail();

        if ($cuota->estado === 'PAGADA') {
            return back()->with('info', 'Esta cuota ya estaba registrada como pagada.');
        }

        DB::table('cuotas')->where('id', $cuotaId)->update([
            'estado' => 'PAGADA',
            'fecha_pago_efectivo' => $request->fecha_pago,
            'monto_pagado' => $request->monto_pagado,
            'updated_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        // ── Registrar ingreso en Caja Capital por cobro de cuota ──
        $cuota = DB::table('cuotas')->where('id', $cuotaId)->first();
        $venta = DB::table('ventas')->where('id', $cuota->venta_id)->first();
        try {
            $this->cajaService->ingresoCapital(
                "Cobro cuota #{$cuota->numero_cuota}/{$cuota->total_cuotas} — Venta {$venta->numero_venta}",
                'USD',
                (float) $request->monto_pagado,
                (float) $request->monto_pagado,
                (int) $cuotaId,
                'cuota'
            );
        } catch (\RuntimeException $e) {
            Log::warning('PlanCuotasWebController pagarCuota: no se pudo registrar movimiento en caja: ' . $e->getMessage());
        }

        // ── Enviar recibo de pago al cliente por email (falla silenciosamente) ──
        try {
            app(\App\Domain\Sales\Events\Listeners\SendCuotaPagadaEmail::class)
                ->sendRecibo((int) $cuotaId, (int) Auth::id());
        } catch (\Throwable) {
            // Never let an email failure break the payment flow
        }

        return back()->with('success', 'Cuota marcada como pagada.')->with('show_print_cuota', $cuotaId);
    }
}
