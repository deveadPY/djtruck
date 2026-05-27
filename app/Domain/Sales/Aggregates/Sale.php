<?php

declare(strict_types=1);

namespace App\Domain\Sales\Aggregates;

use App\Domain\Sales\Exceptions\SalePriceInconsistencyException;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Collection;

/**
 * Sale Aggregate Root.
 *
 * Guardian de las invariantes de una venta:
 * - Una venta siempre tiene al menos un item.
 * - La suma de items debe coincidir con el precio total.
 * - Una venta CUOTAS debe tener un plan asociado.
 */
final class Sale
{
    private ?SaleId $id;
    private int $clienteId;
    private string $numeroVenta;
    private Money $precioTotal;
    private Money $descuento;
    private Currency $monedaVenta;
    private string $modalidadPago;
    private string $estado;
    private Collection $items;
    private Collection $payments;
    private ?int $planCuotasId;

    private function __construct(
        int $clienteId,
        string $numeroVenta,
        Money $precioTotal,
        Money $descuento,
        Currency $monedaVenta,
        string $modalidadPago,
        string $estado
    ) {
        $this->id = null;
        $this->clienteId = $clienteId;
        $this->numeroVenta = $numeroVenta;
        $this->precioTotal = $precioTotal;
        $this->descuento = $descuento;
        $this->monedaVenta = $monedaVenta;
        $this->modalidadPago = $modalidadPago;
        $this->estado = $estado;
        $this->items = new Collection();
        $this->payments = new Collection();
        $this->planCuotasId = null;
    }

    public static function create(
        int $clienteId,
        string $numeroVenta,
        Money $precioTotal,
        Money $descuento,
        Currency $monedaVenta,
        string $modalidadPago,
        string $estado = 'EN_PROCESO'
    ): self {
        return new self(
            $clienteId,
            $numeroVenta,
            $precioTotal,
            $descuento,
            $monedaVenta,
            $modalidadPago,
            $estado
        );
    }

    public function withId(SaleId $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function addItem(SaleItem $item): void
    {
        $this->items->push($item);
    }

    public function addPayment(Payment $payment): void
    {
        $this->payments->push($payment);
    }

    public function assignInstallmentPlan(int $planCuotasId): void
    {
        $this->planCuotasId = $planCuotasId;
    }

    public function precioFinal(): Money
    {
        return $this->precioTotal->subtract($this->descuento);
    }

    public function totalItems(): Money
    {
        return $this->items->reduce(
            fn(Money $carry, SaleItem $item) => $carry->add($item->subtotal()),
            Money::zero($this->monedaVenta)
        );
    }

    public function totalPaid(): Money
    {
        return $this->payments->reduce(
            fn(Money $carry, Payment $payment) => $carry->add($payment->amount),
            Money::zero($this->monedaVenta)
        );
    }

    public function totalBookValue(): Money
    {
        return $this->items->reduce(
            fn(Money $carry, SaleItem $item) => $carry->add($item->costoTotal()),
            Money::zero($this->monedaVenta)
        );
    }

    public function margenBruto(): Money
    {
        return $this->precioFinal()->subtract($this->totalBookValue());
    }

    public function validateInvariants(): void
    {
        if ($this->items->isEmpty()) {
            throw SalePriceInconsistencyException::noItems();
        }

        $calculated = $this->totalItems()->amount;
        $expected = $this->precioTotal->amount;

        if (abs($calculated - $expected) > 0.01) {
            throw SalePriceInconsistencyException::priceMismatch($expected, $calculated);
        }

        if ($this->modalidadPago === 'CUOTAS' && $this->planCuotasId === null) {
            throw SalePriceInconsistencyException::noItems();
        }
    }

    public function getId(): ?SaleId { return $this->id; }
    public function getClienteId(): int { return $this->clienteId; }
    public function getNumeroVenta(): string { return $this->numeroVenta; }
    public function getPrecioTotal(): Money { return $this->precioTotal; }
    public function getDescuento(): Money { return $this->descuento; }
    public function getMonedaVenta(): Currency { return $this->monedaVenta; }
    public function getModalidadPago(): string { return $this->modalidadPago; }
    public function getEstado(): string { return $this->estado; }
    public function getItems(): Collection { return $this->items; }
    public function getPayments(): Collection { return $this->payments; }
    public function getPlanCuotasId(): ?int { return $this->planCuotasId; }
}
