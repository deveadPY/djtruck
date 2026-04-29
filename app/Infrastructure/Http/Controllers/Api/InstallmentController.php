<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Sales\DTOs\PayInstallmentData;
use App\Application\Sales\PayInstallmentUseCase;
use App\Domain\Sales\Services\InstallmentGenerator;
use App\Domain\Sales\ValueObjects\InstallmentPlan;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Infrastructure\Persistence\Eloquent\Models\InstallmentModel;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class InstallmentController extends BaseApiController
{
    public function __construct(
        private readonly InstallmentGenerator $generator,
        private readonly CurrencyConverter    $currency,
        private readonly PayInstallmentUseCase $payInstallment,
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
        $validated = $request->validate([
            'monto_pagado' => 'required|numeric|min:0.01',
            'moneda'       => 'required|in:USD,PYG,BRL',
            'caja_id'      => 'nullable|integer|exists:cajas,id',
            'fecha_pago'   => 'nullable|date',
        ]);

        // Convertir a USD si la moneda no es USD
        $moneda   = Currency::from($validated['moneda']);
        $montoUsd = $moneda === Currency::USD
            ? (float) $validated['monto_pagado']
            : $this->currency->toBaseCurrency((float) $validated['monto_pagado'], $moneda)->amount;

        try {
            $cuota = $this->payInstallment->execute(new PayInstallmentData(
                cuotaId:     $id,
                montoPagado: $montoUsd,
                fechaPago:   $validated['fecha_pago'] ?? now()->toDateString(),
                userId:      (int) auth()->id(),
                cajaId:      isset($validated['caja_id']) ? (int) $validated['caja_id'] : null,
            ));
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), null, 409);
        }

        return $this->successResponse($cuota, 'Cuota registrada como pagada.');
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
