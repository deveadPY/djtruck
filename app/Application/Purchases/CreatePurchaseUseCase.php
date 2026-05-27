<?php

declare(strict_types=1);

namespace App\Application\Purchases;

use App\Domain\Purchases\Calculator\PurchaseCalculator;
use App\Domain\Purchases\Processors\PurchaseDocumentProcessor;
use App\Domain\Purchases\Processors\PurchaseItemProcessor;
use App\Domain\Purchases\Repositories\PurchaseRepositoryInterface;
use App\Domain\Purchases\Validators\PurchaseValidator;
use App\Domain\Shared\ValueObjects\Currency;
use App\Infrastructure\Persistence\Eloquent\Models\PurchaseModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Caso de uso: registrar una compra de repuestos a un proveedor.
 *
 * Orquesta validación, persistencia, actualización de stock,
 * movimiento de caja y registro de factura/documentos.
 */
class CreatePurchaseUseCase
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly PurchaseValidator $validator,
        private readonly PurchaseCalculator $calculator,
        private readonly PurchaseItemProcessor $itemProcessor,
        private readonly PurchaseDocumentProcessor $documentProcessor
    ) {}

    public function execute(CreatePurchaseDTO $dto): PurchaseModel
    {
        $this->validator->validate($dto->items, $dto->proveedorId, $dto->tasaCambio);

        $moneda = Currency::from($dto->monedaCompra);
        $totalMoneda = $this->calculator->calculateTotalInMoney($dto->items);
        $totalUsd = $this->calculator->convertToUsd($totalMoneda, $moneda, $dto->tasaCambio);

        return DB::transaction(function () use ($dto, $moneda, $totalMoneda, $totalUsd) {
            $cajaCapitalId = $this->resolveCajaCapitalId();

            $purchase = $this->createPurchase($dto, $totalMoneda, $totalUsd, $cajaCapitalId);

            $this->itemProcessor->process(
                (int) $purchase->id,
                $dto->items,
                $moneda,
                $dto->tasaCambio
            );

            $this->registerCashMovement(
                (int) $purchase->id,
                $dto,
                $totalMoneda,
                $totalUsd,
                $cajaCapitalId
            );

            $facturaId = $this->createSupplierInvoice($dto, $purchase, $totalMoneda, $totalUsd);

            $this->documentProcessor->process(
                (int) $purchase->id,
                $facturaId,
                $dto->adjuntos ?? []
            );

            return $purchase;
        });
    }

    private function resolveCajaCapitalId(): ?int
    {
        return DB::table('cajas')->where('codigo', 'CAJA_CAPITAL')->value('id');
    }

    private function createPurchase(
        CreatePurchaseDTO $dto,
        float $totalMoneda,
        float $totalUsd,
        ?int $cajaId
    ): PurchaseModel {
        return $this->purchaseRepository->create([
            'proveedor_id'       => $dto->proveedorId,
            'numero_factura'     => $dto->numeroFactura,
            'fecha_compra'       => $dto->fechaCompra,
            'moneda_compra'      => $dto->monedaCompra,
            'monto_total_moneda' => $totalMoneda,
            'monto_total_usd'    => $totalUsd,
            'tasa_cambio'        => $dto->tasaCambio,
            'observaciones'      => $dto->observaciones,
            'caja_id'            => $cajaId,
            'created_by'         => Auth::id(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function registerCashMovement(
        int $compraId,
        CreatePurchaseDTO $dto,
        float $totalMoneda,
        float $totalUsd,
        ?int $cajaId
    ): void {
        if ($cajaId === null) {
            return;
        }

        $proveedor = DB::table('proveedores')->where('id', $dto->proveedorId)->first();
        $razonSocial = $proveedor->razon_social ?? 'Desconocido';

        DB::table('movimientos_caja')->insert([
            'caja_id'       => $cajaId,
            'tipo'          => 'EGRESO',
            'concepto'      => "Compra de productos - Fac. " . ($dto->numeroFactura ?: 'S/N') . " - Prov: {$razonSocial}",
            'moneda'        => $dto->monedaCompra,
            'monto'         => $totalMoneda,
            'monto_usd'     => $totalUsd,
            'ref_type'      => 'compra',
            'referencia_id' => $compraId,
            'created_by'    => Auth::id(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    private function createSupplierInvoice(
        CreatePurchaseDTO $dto,
        PurchaseModel $purchase,
        float $totalMoneda,
        float $totalUsd
    ): int {
        return DB::table('facturas_proveedores')->insertGetId([
            'proveedor_id'   => $dto->proveedorId,
            'numero_factura' => $dto->numeroFactura ?: ('COMP-' . $purchase->id),
            'fecha_factura'  => $dto->fechaCompra,
            'destino'        => 'REPOSICION',
            'compra_id'      => $purchase->id,
            'moneda'         => $dto->monedaCompra,
            'subtotal'       => $totalMoneda,
            'impuestos'      => 0,
            'total_usd'      => $totalUsd,
            'estado'         => 'PAGADA',
            'descripcion'    => "Compra de repuestos vinculada. " . ($dto->observaciones ?? ''),
            'created_by'     => Auth::id(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }
}
