<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Infrastructure\Persistence\Eloquent\Models\FacturaElectronicaModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recibe callbacks del proveedor externo de facturación.
 * Actualiza el estado local cuando la factura es APROBADA o RECHAZADA asíncronamente.
 *
 * URL: POST /api/v1/billing/webhook
 * Header: X-Billing-Signature: <hmac-sha256>
 */
final class BillingWebhookController extends BaseApiController
{
    public function handle(Request $request): JsonResponse
    {
        $secret = config('billing.webhook.secret');
        if ($secret !== '' && !$this->verifySignature($request, $secret)) {
            Log::warning('billing.webhook.invalid_signature', ['ip' => $request->ip()]);
            return $this->errorResponse('Firma inválida.', null, 401);
        }

        $payload = $request->json()->all();
        $cdc     = $payload['cdc'] ?? null;
        $estado  = $payload['status'] ?? null;

        if (!$cdc || !$estado) {
            return $this->errorResponse('Payload incompleto: cdc y status son requeridos.', null, 422);
        }

        $factura = FacturaElectronicaModel::where('cdc', $cdc)->first();
        if (!$factura) {
            Log::info('billing.webhook.unknown_cdc', ['cdc' => $cdc]);
            return $this->successResponse(null, 'CDC desconocido — ignorado.');
        }

        $factura->fill([
            'estado'       => $estado,
            'aprobada_at'  => $estado === 'APROBADO'  ? ($factura->aprobada_at  ?? now()) : $factura->aprobada_at,
            'cancelada_at' => $estado === 'CANCELADO' ? ($factura->cancelada_at ?? now()) : $factura->cancelada_at,
            'url_pdf'      => $payload['pdf_url']   ?? $factura->url_pdf,
            'url_xml'      => $payload['xml_url']   ?? $factura->url_xml,
            'qr_code'      => $payload['qr_code']   ?? $factura->qr_code,
            'error_code'   => $payload['error_code']    ?? null,
            'error_message'=> $payload['error_message'] ?? null,
            'raw_response' => array_merge($factura->raw_response ?? [], ['webhook' => $payload]),
        ])->save();

        Log::info('billing.webhook.applied', [
            'cdc'       => $cdc,
            'estado'    => $estado,
            'factura_id'=> $factura->id,
        ]);

        return $this->successResponse(['estado' => $estado], 'Webhook procesado.');
    }

    private function verifySignature(Request $request, string $secret): bool
    {
        $received = $request->header('X-Billing-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($expected, $received);
    }
}
