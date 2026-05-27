<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\Contracts\BillingProviderInterface;
use App\Infrastructure\Persistence\Eloquent\Models\FacturaElectronicaModel;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class CancelInvoiceUseCase
{
    public function __construct(
        private readonly BillingProviderInterface $provider,
    ) {}

    public function execute(int $facturaId, string $motivo): FacturaElectronicaModel
    {
        $factura = FacturaElectronicaModel::find($facturaId);
        if (!$factura) {
            throw new RuntimeException("Factura electrónica {$facturaId} no encontrada.");
        }
        if (!$factura->cdc) {
            throw new RuntimeException("La factura no tiene CDC — no puede cancelarse en el proveedor.");
        }
        if ($factura->isCancelada()) {
            return $factura;
        }

        $result = $this->provider->cancel($factura->cdc, $motivo);

        $factura->fill([
            'estado'              => $result->estado,
            'cancelada_at'        => $result->success ? now() : $factura->cancelada_at,
            'motivo_cancelacion'  => $motivo,
            'error_code'          => $result->errorCode,
            'error_message'       => $result->errorMessage,
            'raw_response'        => array_merge($factura->raw_response ?? [], ['cancel' => $result->rawResponse]),
            'updated_by'          => Auth::id(),
        ]);
        $factura->save();

        return $factura;
    }
}
