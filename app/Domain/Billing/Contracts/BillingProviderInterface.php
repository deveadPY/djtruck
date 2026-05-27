<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

use App\Domain\Billing\DTOs\InvoiceRequest;
use App\Domain\Billing\DTOs\InvoiceResult;
use App\Domain\Billing\DTOs\CancelInvoiceResult;

/**
 * Port (Hexagonal): contrato agnóstico al proveedor de facturación electrónica.
 *
 * Implementaciones posibles:
 *   - FacturaSendAdapter
 *   - EKuatiaAdapter
 *   - BukealaAdapter
 *   - NullAdapter (para desarrollo/test)
 *
 * La capa de Application llama SOLO a esta interfaz, nunca al SDK específico.
 */
interface BillingProviderInterface
{
    /**
     * Emite una factura electrónica.
     * Idempotente: si la venta ya tiene CDC emitido, devuelve el resultado existente.
     */
    public function emit(InvoiceRequest $request): InvoiceResult;

    /**
     * Cancela una factura electrónica.
     */
    public function cancel(string $cdc, string $motivo): CancelInvoiceResult;

    /**
     * Consulta estado actual de una factura ya emitida.
     */
    public function check(string $cdc): InvoiceResult;

    /**
     * Identificador del proveedor (para logs y selección por config).
     */
    public function providerName(): string;
}
