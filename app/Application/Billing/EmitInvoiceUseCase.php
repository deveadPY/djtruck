<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\Contracts\BillingProviderInterface;
use App\Domain\Billing\DTOs\InvoiceRequest;
use App\Infrastructure\Persistence\Eloquent\Models\FacturaElectronicaModel;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Emite factura electrónica a través del proveedor configurado.
 *
 * Reglas:
 * - Idempotente: si ya existe factura APROBADA para la venta, no reemite.
 * - Persiste estado local ANTES de llamar al provider (estado=PENDIENTE).
 * - Tras respuesta del provider, actualiza estado + cdc + urls.
 * - Errores NO bloquean la venta — quedan en estado ERROR para reintentar.
 */
final class EmitInvoiceUseCase
{
    public function __construct(
        private readonly BillingProviderInterface $provider,
    ) {}

    public function execute(int $ventaId, string $tipoDocumento = 'FACTURA'): FacturaElectronicaModel
    {
        $venta = SaleModel::with(['cliente'])->find($ventaId);
        if (!$venta) {
            throw new RuntimeException("Venta {$ventaId} no encontrada.");
        }

        // Idempotencia: si ya está aprobada, devolverla
        $existente = FacturaElectronicaModel::where('venta_id', $ventaId)
            ->whereIn('estado', ['APROBADO', 'ENVIADA', 'PENDIENTE'])
            ->orderByDesc('id')
            ->first();
        if ($existente && $existente->isAprobada()) {
            return $existente;
        }

        $items = DB::table('venta_items')
            ->where('venta_id', $ventaId)
            ->whereNull('deleted_at')
            ->get()
            ->map(fn($it) => [
                'descripcion'     => $it->descripcion,
                'cantidad'        => (float) $it->cantidad,
                'precio_unitario' => (float) $it->precio_unitario_usd,
                'subtotal'        => (float) $it->subtotal_usd,
                'iva_pct'         => 10.0, // IVA Paraguay default — config por línea si aplica
            ])
            ->toArray();

        $pagos = DB::table('detalles_pago')
            ->where('venta_id', $ventaId)
            ->whereNull('deleted_at')
            ->get()
            ->map(fn($p) => [
                'tipo'   => $p->tipo_pago,
                'monto'  => (float) $p->monto_usd,
                'moneda' => $p->moneda,
            ])
            ->toArray();

        $request = new InvoiceRequest(
            ventaId:         $ventaId,
            tipoDocumento:   $tipoDocumento,
            numeroDocumento: $venta->numero_venta,
            fechaEmision:    now()->toIso8601String(),
            monedaCodigo:    $venta->moneda_venta,
            tasaCambio:      (float) ($venta->tasa_cambio_venta ?? 1),
            totalNeto:       round((float) $venta->precio_venta_usd / 1.1, 4),
            totalIva:        round((float) $venta->precio_venta_usd - ((float) $venta->precio_venta_usd / 1.1), 4),
            totalGeneral:    (float) $venta->precio_venta_usd,
            cliente: [
                'id'           => $venta->cliente->id,
                'ruc'          => $venta->cliente->ruc,
                'razon_social' => $venta->cliente->razon_social,
                'email'        => $venta->cliente->email,
                'direccion'    => $venta->cliente->direccion,
                'telefono'     => $venta->cliente->telefono,
            ],
            items:           $items,
            pagos:           $pagos,
            observaciones:   $venta->observaciones,
        );

        // Crear o reutilizar registro PENDIENTE
        $factura = $existente ?: new FacturaElectronicaModel();
        $factura->fill([
            'venta_id'        => $ventaId,
            'tipo_documento'  => $tipoDocumento,
            'numero_documento'=> $request->numeroDocumento,
            'provider'        => $this->provider->providerName(),
            'estado'          => 'PENDIENTE',
            'total_neto'      => $request->totalNeto,
            'total_iva'       => $request->totalIva,
            'total_general'   => $request->totalGeneral,
            'moneda'          => $request->monedaCodigo,
            'emitida_at'      => now(),
            'created_by'      => Auth::id() ?? $factura->created_by,
            'updated_by'      => Auth::id(),
        ]);
        $factura->save();

        // Llamar al provider
        $result = $this->provider->emit($request);

        $factura->fill([
            'estado'        => $result->estado,
            'cdc'           => $result->cdc,
            'url_pdf'       => $result->urlPdf,
            'url_xml'       => $result->urlXml,
            'qr_code'       => $result->qrCode,
            'error_code'    => $result->errorCode,
            'error_message' => $result->errorMessage,
            'raw_response'  => $result->rawResponse,
            'aprobada_at'   => $result->estado === 'APROBADO' ? now() : null,
            'updated_by'    => Auth::id(),
        ]);
        $factura->save();

        return $factura;
    }
}
