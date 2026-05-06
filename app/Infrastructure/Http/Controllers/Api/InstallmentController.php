<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Infrastructure\Persistence\Eloquent\Models\InstallmentModel;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class InstallmentController extends BaseApiController
{
    public function __construct(
        private readonly InstallmentGenerator $generator,
        private readonly CurrencyConverter    $currency,
        private readonly CajaService          $cajas,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = InstallmentModel::query()
            ->with('venta')
            ->when($request->estado,   fn($q) => $q->where('estado', $request->estado))
            ->when($request->venta_id, fn($q) => $q->where('venta_id', $request->venta_id));

        return $this->paginatedResponse($query->orderBy('fecha_vencimiento')->paginate(30));
    }

    public function overdue(): JsonResponse
    {
        $overdue = InstallmentModel::where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->with('venta')
            ->orderBy('fecha_vencimiento')
            ->paginate(30);

        return $this->paginatedResponse($overdue, 'Cuotas vencidas.');
    }

    public function dueToday(): JsonResponse
    {
        $due = InstallmentModel::where('estado', 'PENDIENTE')
            ->whereDate('fecha_vencimiento', now()->toDateString())
            ->with('venta')
            ->get();

        return $this->successResponse($due);
    }

    public function show(int $id): JsonResponse
    {
        return $this->successResponse(InstallmentModel::with('venta')->findOrFail($id));
    }

    public function pay(Request $request, int $id): JsonResponse
    {
        $cuota     = InstallmentModel::findOrFail($id);
        $validated = $request->validate([
            'monto_pagado' => 'required|numeric|min:0.01',
            'moneda'       => 'required|in:USD,PYG,BRL',
            'caja_id'      => 'nullable|integer|exists:cajas,id',
        ]);

        // Si no se especifica caja, usar Caja Capital por defecto
        $validated['caja_id'] = $validated['caja_id'] ?? $this->cajas->cajaCapitalId();

        if ($cuota->estado === 'PAGADA') {
            return $this->errorResponse('Esta cuota ya fue pagada.', null, 409);
        }

        DB::transaction(function () use ($cuota, $validated) {
            $moneda    = Currency::from($validated['moneda']);
            $montoUsd  = $moneda === Currency::USD
                ? $validated['monto_pagado']
                : $this->currency->toBaseCurrency($validated['monto_pagado'], $moneda)->amount;

            $diasMora     = $cuota->diasMora();
            $interesExtra = $diasMora > 0
                ? round($cuota->monto_total * (config('erp.installments.tasa_mora_diaria_pct', 0.1) / 100) * $diasMora, 2)
                : 0;

            $cuota->update([
                'estado'             => 'PAGADA',
                'fecha_pago_efectivo'=> now()->toDateString(),
                'monto_pagado'       => $validated['monto_pagado'],
                'interes_mora'       => $interesExtra,
                'caja_cobro_id'      => $validated['caja_id'],
                'updated_by'         => auth()->id(),
            ]);

            // Registrar en movimientos de caja
            DB::table('movimientos_caja')->insert([
                'caja_id'       => $validated['caja_id'],
                'tipo'          => 'INGRESO',
                'concepto'      => "Cobro cuota #{$cuota->numero_cuota}/{$cuota->total_cuotas} - Venta #{$cuota->venta_id}",
                'referencia_id' => $cuota->id,
                'ref_type'      => 'cuota',
                'moneda'        => $validated['moneda'],
                'monto'         => $validated['monto_pagado'],
                'monto_usd'     => $montoUsd,
                'created_at'    => now(),
                'created_by'    => auth()->id(),
            ]);
        });

        return $this->successResponse($cuota->fresh(), 'Cuota registrada como pagada.');
    }

    public function partialPay(Request $request, int $id): JsonResponse
    {
        $cuota     = InstallmentModel::findOrFail($id);
        $validated = $request->validate([
            'monto_pagado' => 'required|numeric|min:0.01',
            'moneda'       => 'required|in:USD,PYG,BRL',
            'caja_id'      => 'required|integer',
        ]);

        $cuota->update([
            'estado'       => 'PAGADA_PARCIAL',
            'monto_pagado' => $validated['monto_pagado'],
            'updated_by'   => auth()->id(),
        ]);

        return $this->successResponse($cuota->fresh(), 'Pago parcial registrado.');
    }

    public function clientStatement(int $clientId): JsonResponse
    {
        $cuotas = InstallmentModel::whereHas('venta', fn($q) => $q->where('cliente_id', $clientId))
            ->with('venta')
            ->orderBy('fecha_vencimiento')
            ->get();

        $resumen = [
            'total_pendiente' => $cuotas->where('estado', 'PENDIENTE')->sum('monto_total'),
            'total_vencido'   => $cuotas->where('estado', 'PENDIENTE')
                ->where('fecha_vencimiento', '<', now()->toDateString())->sum('monto_total'),
            'total_pagado'    => $cuotas->where('estado', 'PAGADA')->sum('monto_pagado'),
            'proximas_cuotas' => $cuotas->where('estado', 'PENDIENTE')
                ->where('fecha_vencimiento', '>=', now()->toDateString())
                ->take(3)->values(),
        ];

        return $this->successResponse(['cuotas' => $cuotas, 'resumen' => $resumen]);
    }

    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'capital'          => 'required|numeric|min:1',
            'moneda'           => 'required|in:USD,PYG,BRL',
            'numero_cuotas'    => 'required|integer|min:1|max:60',
            'tasa_mensual'     => 'required|numeric|min:0|max:10',
            'tipo'             => 'required|in:FRANCESA,ALEMANA',
            'fecha_primera'    => 'required|date|after:today',
        ]);

        $result = $this->generator->simulate(
            (float) $validated['capital'],
            $validated['moneda'],
            (int)   $validated['numero_cuotas'],
            (float) $validated['tasa_mensual'],
            InstallmentPlan::from($validated['tipo']),
            $validated['fecha_primera'],
        );

        return $this->successResponse($result);
    }
}
