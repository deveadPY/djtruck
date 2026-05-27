<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Adapters;

use App\Domain\Billing\Contracts\BillingProviderInterface;
use App\Domain\Billing\DTOs\CancelInvoiceResult;
use App\Domain\Billing\DTOs\InvoiceRequest;
use App\Domain\Billing\DTOs\InvoiceResult;
use App\Domain\Billing\Exceptions\BillingProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter genérico para una API REST de facturación electrónica.
 * Configurable via .env — endpoints y mapping de payload se definen en config/billing.php.
 *
 * Para integrar un proveedor específico (FacturaSend, eKuatia, Bukeala, etc.),
 * crear un adapter dedicado que extienda este o implemente BillingProviderInterface.
 */
final class GenericApiBillingAdapter implements BillingProviderInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int    $timeout = 30,
        private readonly string $providerName = 'generic-api',
    ) {
        if ($baseUrl === '' || $apiKey === '') {
            throw BillingProviderException::notConfigured($providerName);
        }
    }

    public function emit(InvoiceRequest $request): InvoiceResult
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/invoices", $this->buildPayload($request));

            $data = $response->json();

            if (!$response->successful()) {
                Log::warning('billing.emit.failed', [
                    'provider' => $this->providerName,
                    'status'   => $response->status(),
                    'body'     => $data,
                    'venta_id' => $request->ventaId,
                ]);
                return new InvoiceResult(
                    success:      false,
                    estado:       'RECHAZADO',
                    errorCode:    (string) $response->status(),
                    errorMessage: $data['error'] ?? $data['message'] ?? 'Error HTTP ' . $response->status(),
                    rawResponse:  $data ?? [],
                );
            }

            return new InvoiceResult(
                success:    true,
                estado:     $data['status'] ?? 'PENDIENTE',
                cdc:        $data['cdc']        ?? null,
                numero:     $data['number']     ?? $request->numeroDocumento,
                urlPdf:     $data['pdf_url']    ?? null,
                urlXml:     $data['xml_url']    ?? null,
                qrCode:     $data['qr_code']    ?? null,
                rawResponse: $data,
            );
        } catch (ConnectionException $e) {
            throw BillingProviderException::networkError($this->providerName);
        } catch (\Throwable $e) {
            Log::error('billing.emit.exception', [
                'provider' => $this->providerName,
                'error'    => $e->getMessage(),
                'venta_id' => $request->ventaId,
            ]);
            return InvoiceResult::fromException($e);
        }
    }

    public function cancel(string $cdc, string $motivo): CancelInvoiceResult
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/invoices/{$cdc}/cancel", ['motivo' => $motivo]);

            $data = $response->json();

            if (!$response->successful()) {
                return new CancelInvoiceResult(
                    success:      false,
                    estado:       'RECHAZADO_CANCELACION',
                    errorCode:    (string) $response->status(),
                    errorMessage: $data['error'] ?? 'Error HTTP ' . $response->status(),
                    rawResponse:  $data ?? [],
                );
            }

            return new CancelInvoiceResult(
                success:     true,
                estado:      'CANCELADO',
                rawResponse: $data,
            );
        } catch (\Throwable $e) {
            return new CancelInvoiceResult(
                success:      false,
                estado:       'ERROR',
                errorCode:    (string) $e->getCode(),
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function check(string $cdc): InvoiceResult
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->acceptJson()
                ->get("{$this->baseUrl}/invoices/{$cdc}");

            $data = $response->json();

            return new InvoiceResult(
                success:    $response->successful(),
                estado:     $data['status'] ?? 'PENDIENTE',
                cdc:        $cdc,
                urlPdf:     $data['pdf_url'] ?? null,
                urlXml:     $data['xml_url'] ?? null,
                rawResponse: $data ?? [],
            );
        } catch (\Throwable $e) {
            return InvoiceResult::fromException($e);
        }
    }

    public function providerName(): string
    {
        return $this->providerName;
    }

    /**
     * Mapping payload — sobrescribir en adapter específico si la API lo requiere.
     */
    protected function buildPayload(InvoiceRequest $request): array
    {
        return [
            'reference'      => "VENTA-{$request->ventaId}",
            'document_type'  => $request->tipoDocumento,
            'document_number'=> $request->numeroDocumento,
            'issue_date'     => $request->fechaEmision,
            'currency'       => $request->monedaCodigo,
            'exchange_rate'  => $request->tasaCambio,
            'subtotal'       => $request->totalNeto,
            'tax_total'      => $request->totalIva,
            'total'          => $request->totalGeneral,
            'customer'       => $request->cliente,
            'items'          => $request->items,
            'payments'       => $request->pagos,
            'notes'          => $request->observaciones,
        ];
    }
}
