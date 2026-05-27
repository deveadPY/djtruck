<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Sales\CancelSaleDTO;
use App\Application\Sales\ProcessHybridSaleDTO;
use App\Application\Sales\ProcessHybridSaleUseCase;
use App\Application\Sales\SaleApplicationService;
use App\Infrastructure\Http\Resources\SaleResource;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaleController extends BaseApiController
{
    public function __construct(
        private readonly SaleApplicationService $saleService,
        private readonly ProcessHybridSaleUseCase $hybridSaleUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = SaleModel::with(['vehiculo', 'cliente'])
            ->when($request->estado,     fn($q) => $q->where('estado', $request->estado))
            ->when($request->cliente_id, fn($q) => $q->where('cliente_id', $request->cliente_id));

        return $this->paginatedResponse(
            $query->orderByDesc('created_at')->paginate(20),
            'OK',
            SaleResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        $sale = $this->saleService->findById($id);
        if (!$sale) {
            return $this->errorResponse('Venta no encontrada.', null, 404);
        }
        return $this->successResponse(new SaleResource($sale));
    }

    public function destroy(int $id): JsonResponse
    {
        $sale = $this->saleService->findById($id);
        if (!$sale) {
            return $this->errorResponse('Venta no encontrada.', null, 404);
        }
        if ($sale->estado === 'COMPLETADO') {
            return $this->errorResponse('No se puede eliminar una venta completada.', null, 409);
        }

        $this->saleService->cancel(new CancelSaleDTO(id: $id, motivo: 'Eliminación desde API'));
        return $this->successResponse(null, 'Venta eliminada.');
    }

    /**
     * Venta híbrida: efectivo + canje de vehículo + plan de cuotas en una operación.
     */
    public function procesarVentaConCanje(Request $request): JsonResponse
    {
        $request->validate([
            'vehiculo_id'                          => 'required|integer|exists:vehiculos,id',
            'cliente_id'                           => 'required|integer',
            'vendedor_id'                          => 'nullable|integer',
            'moneda_venta'                         => 'required|in:USD,PYG,BRL',
            'precio_venta'                         => 'required|numeric|min:1',
            'pagos'                                => 'required|array|min:1',
            'pagos.*.tipo'                         => 'required|in:EFECTIVO,TRANSFERENCIA,CHEQUE,VEHICULO_CANJE,PLAN_CUOTAS,TARJETA',
            'pagos.*.monto'                        => 'required|numeric|min:0.01',
            'pagos.*.moneda'                       => 'required|in:USD,PYG,BRL',
            'pagos.*.caja_id'                      => 'nullable|integer',
            'pagos.*.vehiculo_canje.numero_chasis' => 'required_if:pagos.*.tipo,VEHICULO_CANJE|string',
            'pagos.*.vehiculo_canje.marca'         => 'required_if:pagos.*.tipo,VEHICULO_CANJE|string',
            'pagos.*.vehiculo_canje.modelo'        => 'required_if:pagos.*.tipo,VEHICULO_CANJE|string',
            'pagos.*.vehiculo_canje.anio'          => 'required_if:pagos.*.tipo,VEHICULO_CANJE|integer',
            'pagos.*.vehiculo_canje.valor_toma'    => 'required_if:pagos.*.tipo,VEHICULO_CANJE|numeric',
            'pagos.*.plan.tipo'                    => 'required_if:pagos.*.tipo,PLAN_CUOTAS|in:FRANCESA,ALEMANA',
            'pagos.*.plan.numero_cuotas'           => 'required_if:pagos.*.tipo,PLAN_CUOTAS|integer|min:1|max:60',
            'pagos.*.plan.tasa_interes_mensual'    => 'required_if:pagos.*.tipo,PLAN_CUOTAS|numeric|min:0',
            'pagos.*.plan.fecha_primera_cuota'     => 'required_if:pagos.*.tipo,PLAN_CUOTAS|date',
            'observaciones'                        => 'nullable|string|max:1000',
        ]);

        $result = $this->hybridSaleUseCase->execute(ProcessHybridSaleDTO::fromRequest($request));

        return $this->successResponse([
            'venta'             => $result['venta']->fresh(['detallesPago', 'cuotas']),
            'vehiculo_canjeado' => $result['vehiculoCanjeado'],
            'plan_cuotas'       => $result['planCuotas'],
        ], 'Venta con canje procesada exitosamente.', 201);
    }

    public function profitability(int $id): JsonResponse
    {
        $venta = SaleModel::with('vehiculo.gastos')->findOrFail($id);

        return $this->successResponse([
            'venta_id'         => $id,
            'precio_venta_usd' => $venta->precio_venta_usd,
            'valor_libro_usd'  => $venta->valor_libro_snapshot,
            'margen_bruto_usd' => $venta->margen_bruto_usd,
            'margen_pct'       => round($venta->margen_pct, 2),
            'costo_origen_usd' => $venta->vehiculo->costo_origen_usd,
            'total_gastos_usd' => $venta->vehiculo->total_gastos_usd,
            'detalle_gastos'   => $venta->vehiculo->gastos->map(fn($g) => [
                'concepto'  => $g->concepto,
                'monto_usd' => $g->monto_usd,
                'fecha'     => $g->fecha_gasto,
            ]),
        ]);
    }
}
