<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Validators;

use App\Domain\Purchases\Exceptions\InvalidPurchaseDataException;
use App\Domain\Purchases\Exceptions\SupplierNotFoundException;
use Illuminate\Support\Facades\DB;

class PurchaseValidator
{
    public function validate(array $items, int $proveedorId, float $tasaCambio): void
    {
        $this->ensureItemsNotEmpty($items);
        $this->ensureSupplierExists($proveedorId);
        $this->ensureExchangeRateValid($tasaCambio);
        $this->validateItems($items);
    }

    private function ensureItemsNotEmpty(array $items): void
    {
        if (empty($items)) {
            throw InvalidPurchaseDataException::noItems();
        }
    }

    private function ensureSupplierExists(int $proveedorId): void
    {
        $exists = DB::table('proveedores')
            ->where('id', $proveedorId)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            throw new SupplierNotFoundException($proveedorId);
        }
    }

    private function ensureExchangeRateValid(float $tasaCambio): void
    {
        if ($tasaCambio <= 0) {
            throw InvalidPurchaseDataException::invalidExchangeRate($tasaCambio);
        }
    }

    private function validateItems(array $items): void
    {
        foreach ($items as $item) {
            $repuestoId = (int) ($item['repuesto_id'] ?? 0);
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio_compra'] ?? 0);

            if ($cantidad <= 0) {
                throw InvalidPurchaseDataException::invalidQuantity($repuestoId, $cantidad);
            }

            if ($precio <= 0) {
                throw InvalidPurchaseDataException::invalidPrice($repuestoId, $precio);
            }

            $repuestoExists = DB::table('stock_repuestos')->where('id', $repuestoId)->exists();
            if (!$repuestoExists) {
                throw InvalidPurchaseDataException::repuestoNotFound($repuestoId);
            }
        }
    }
}
