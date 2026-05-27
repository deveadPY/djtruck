<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Aggregates;

use App\Domain\Purchases\Exceptions\InvalidPurchaseDataException;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Collection;

/**
 * Purchase Aggregate Root.
 *
 * Guardian de invariantes de la compra:
 * - Mínimo un item
 * - Total en moneda original = suma de subtotales
 * - Proveedor obligatorio
 */
final class Purchase
{
    private ?PurchaseId $id;
    private int $proveedorId;
    private ?string $numeroFactura;
    private string $fechaCompra;
    private Currency $monedaCompra;
    private float $tasaCambio;
    private Collection $items;
    private ?string $observaciones;

    private function __construct(
        int $proveedorId,
        ?string $numeroFactura,
        string $fechaCompra,
        Currency $monedaCompra,
        float $tasaCambio,
        ?string $observaciones
    ) {
        $this->id = null;
        $this->proveedorId = $proveedorId;
        $this->numeroFactura = $numeroFactura;
        $this->fechaCompra = $fechaCompra;
        $this->monedaCompra = $monedaCompra;
        $this->tasaCambio = $tasaCambio;
        $this->items = new Collection();
        $this->observaciones = $observaciones;
    }

    public static function create(
        int $proveedorId,
        ?string $numeroFactura,
        string $fechaCompra,
        Currency $monedaCompra,
        float $tasaCambio,
        ?string $observaciones = null
    ): self {
        if ($tasaCambio <= 0) {
            throw InvalidPurchaseDataException::invalidExchangeRate($tasaCambio);
        }

        return new self(
            $proveedorId,
            $numeroFactura,
            $fechaCompra,
            $monedaCompra,
            $tasaCambio,
            $observaciones
        );
    }

    public function addItem(int $repuestoId, float $cantidad, Money $precioCompra): void
    {
        if ($cantidad <= 0) {
            throw InvalidPurchaseDataException::invalidQuantity($repuestoId, $cantidad);
        }
        if (!$precioCompra->isPositive()) {
            throw InvalidPurchaseDataException::invalidPrice($repuestoId, $precioCompra->amount);
        }

        $this->items->push([
            'repuesto_id'   => $repuestoId,
            'cantidad'      => $cantidad,
            'precio_compra' => $precioCompra,
            'subtotal'      => $precioCompra->multiply($cantidad),
        ]);
    }

    public function withId(PurchaseId $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function totalMoney(): Money
    {
        return $this->items->reduce(
            fn(Money $carry, array $item) => $carry->add($item['subtotal']),
            Money::zero($this->monedaCompra)
        );
    }

    public function validateInvariants(): void
    {
        if ($this->items->isEmpty()) {
            throw InvalidPurchaseDataException::noItems();
        }
    }

    public function getId(): ?PurchaseId      { return $this->id; }
    public function getProveedorId(): int     { return $this->proveedorId; }
    public function getNumeroFactura(): ?string { return $this->numeroFactura; }
    public function getFechaCompra(): string  { return $this->fechaCompra; }
    public function getMonedaCompra(): Currency { return $this->monedaCompra; }
    public function getTasaCambio(): float    { return $this->tasaCambio; }
    public function getItems(): Collection    { return $this->items; }
    public function getObservaciones(): ?string { return $this->observaciones; }
}
