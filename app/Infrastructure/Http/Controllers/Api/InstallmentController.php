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
        private readonly \App\Application\Installments\PayInstallmentsUseCase $payUseCase,
        private readonly \App\Application\Installments\LiquidatePlanUseCase $liquidateUseCase,
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
            'monto_pagado'               => 'required|numeric|min:0.01',
            'moneda'                     => 'required|in:USD,PYG,BRL',
            'fecha_pago'                 => 'nullable|date',
            'caja_id'                    => 'nullable|integer|exists:cajas,id',
            'aplicar_descuento_anticipo'  => 'nullable|boolean',
            'descuento_anticipo_pct'     => 'nullable|numeric|min:0|max:50',
            'descuento_proporcional'     => 'nullable|boolean',
        ]);

        $cuota = InstallmentModel::findOrFail($id);
        if ($cuota->estado === 'PAGADA') {
            return $this->errorResponse('Esta cuota ya fue pagada.', null, 409);
        }

        $dto = \App\Application\Installments\PayInstallmentsDTO::fromArray([
            'cuotas_ids'                 => [$id],
            'monto_pagado'               => (float) $validated['monto_pagado'],
            'moneda'                     => $validated['moneda'],
            'fecha_pago'                 => $validated['fecha_pago'] ?? now()->toDateString(),
            'caja_id'                    => $validated['caja_id'] ?? null,
            'aplicar_descuento_anticipo'  => (bool) ($validated['aplicar_descuento_anticipo'] ?? false),
            'descuento_anticipo_pct'     => $validated['descuento_anticipo_pct'] ?? null,
            'descuento_proporcional'     => (bool) ($validated['descuento_proporcional'] ?? false),
            'observaciones'              => null,
            'user_id'                    => (int) auth()->id(),
            'ip_address'                 => $request->ip(),
        ]);

        try {
            $result = $this->payUseCase->execute($dto);
            return $this->successResponse($result, 'Cuota pagada correctamente.');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    public function partialPay(Request $request, int $id): JsonResponse
    {
        // Pago parcial: igual que pay() pero el use case determina si es parcial o total
        return $this->pay($request, $id);
    }

    /**
     * Liquida el plan completo de cuotas.
     */
    public function liquidate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'                      => 'required|integer|exists:planes_cuotas,id',
            'fecha_liquidacion'            => 'required|date',
            'caja_id'                      => 'nullable|integer|exists:cajas,id',
            'aplicar_descuento_liquidacion' => 'nullable|boolean',
            'descuento_liquidacion_pct'    => 'nullable|numeric|min:0|max:100',
            'observaciones'                => 'nullable|string|max:500',
        ]);

        $dto = \App\Application\Installments\LiquidatePlanDTO::fromArray([
            'plan_id'                      => (int) $validated['plan_id'],
            'fecha_liquidacion'            => $validated['fecha_liquidacion'],
            'caja_id'                      => $validated['caja_id'] ?? null,
            'aplicar_descuento_liquidacion' => (bool) ($validated['aplicar_descuento_liquidacion'] ?? false),
            'descuento_liquidacion_pct'    => $validated['descuento_liquidacion_pct'] ?? null,
            'observaciones'                => $validated['observaciones'] ?? null,
            'user_id'                      => (int) auth()->id(),
            'ip_address'                   => $request->ip(),
        ]);

        try {
            $result = $this->liquidateUseCase->execute($dto);
            return $this->successResponse($result, 'Plan liquidado correctamente.');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
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
