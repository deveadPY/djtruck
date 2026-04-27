<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Infrastructure\SIFEN\ElectronicInvoicingService;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SifenController extends BaseApiController
{
    public function __construct(private readonly ElectronicInvoicingService $sifen) {}

    public function status(): JsonResponse
    {
        return $this->successResponse([
            'ambiente'        => config('sifen.ambiente'),
            'ruc_emisor'      => config('sifen.ruc_emisor'),
            'numero_timbrado' => config('sifen.numero_timbrado'),
            'pendientes'      => SaleModel::where('tiene_factura_electronica', 0)
                ->where('estado', 'COMPLETADO')->count(),
        ]);
    }

    public function emit(int $saleId): JsonResponse
    {
        $venta  = SaleModel::findOrFail($saleId);
        $result = $this->sifen->emitirFactura($venta);
        return $this->successResponse($result, 'Factura emitida correctamente.');
    }

    public function consult(string $cdc): JsonResponse
    {
        $venta = SaleModel::where('cdc_sifen', $cdc)->first();
        return $this->successResponse([
            'cdc'   => $cdc,
            'venta' => $venta,
            'nota'  => 'Consulta en tiempo real a SIFEN requiere conexión.',
        ]);
    }

    public function cancel(string $cdc): JsonResponse
    {
        return $this->errorResponse('Cancelación no implementada en sandbox.', null, 501);
    }

    public function pending(): JsonResponse
    {
        $pending = SaleModel::where('tiene_factura_electronica', 0)
            ->where('estado', 'COMPLETADO')
            ->with('vehiculo', 'cliente')
            ->orderBy('fecha_venta')
            ->paginate(20);

        return $this->paginatedResponse($pending, 'Ventas pendientes de facturación electrónica.');
    }

    public function retryPending(): JsonResponse
    {
        $pending = SaleModel::where('tiene_factura_electronica', 0)
            ->where('estado', 'COMPLETADO')
            ->limit(10)->get();

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($pending as $venta) {
            try {
                $this->sifen->emitirFactura($venta);
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "Venta #{$venta->id}: {$e->getMessage()}";
            }
        }

        return $this->successResponse($results, "Re-emisión completada: {$results['success']} OK, {$results['failed']} errores.");
    }
}
