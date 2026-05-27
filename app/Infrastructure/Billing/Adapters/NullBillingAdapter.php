<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Adapters;

use App\Domain\Billing\Contracts\BillingProviderInterface;
use App\Domain\Billing\DTOs\CancelInvoiceResult;
use App\Domain\Billing\DTOs\InvoiceRequest;
use App\Domain\Billing\DTOs\InvoiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Adapter Null: no llama a ninguna API.
 * Útil en desarrollo y tests — emula una respuesta exitosa con CDC fake.
 */
final class NullBillingAdapter implements BillingProviderInterface
{
    public function emit(InvoiceRequest $request): InvoiceResult
    {
        $fakeCdc = '01-' . str_pad((string) $request->ventaId, 14, '0', STR_PAD_LEFT) . '-' . Str::random(8);

        Log::info('billing.null.emit', [
            'venta_id' => $request->ventaId,
            'fake_cdc' => $fakeCdc,
        ]);

        return new InvoiceResult(
            success: true,
            estado:  'APROBADO',
            cdc:     $fakeCdc,
            numero:  $request->numeroDocumento,
            urlPdf:  null,
            urlXml:  null,
            qrCode:  "null-driver://venta/{$request->ventaId}",
            rawResponse: ['driver' => 'null', 'note' => 'No real billing API called.'],
        );
    }

    public function cancel(string $cdc, string $motivo): CancelInvoiceResult
    {
        Log::info('billing.null.cancel', ['cdc' => $cdc, 'motivo' => $motivo]);

        return new CancelInvoiceResult(
            success: true,
            estado:  'CANCELADO',
            rawResponse: ['driver' => 'null'],
        );
    }

    public function check(string $cdc): InvoiceResult
    {
        return new InvoiceResult(
            success: true,
            estado:  'APROBADO',
            cdc:     $cdc,
            rawResponse: ['driver' => 'null'],
        );
    }

    public function providerName(): string
    {
        return 'null';
    }
}
