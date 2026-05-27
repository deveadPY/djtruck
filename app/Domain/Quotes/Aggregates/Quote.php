<?php

declare(strict_types=1);

namespace App\Domain\Quotes\Aggregates;

use App\Domain\Quotes\Exceptions\QuoteException;
use App\Domain\Quotes\ValueObjects\QuoteStatus;

/**
 * Quote Aggregate Root — presupuesto de venta.
 *
 * Invariantes:
 *  - Al menos 1 item.
 *  - vigencia_hasta >= fecha_emision.
 *  - Estado sigue máquina de estados.
 *  - Solo puede convertirse a venta si está ACEPTADO y no vencido.
 */
final class Quote
{
    private ?int $id;
    private string $numero;
    private int $clienteId;
    private ?int $leadId;
    private ?int $vendedorId;
    private QuoteStatus $estado;
    private \DateTimeImmutable $fechaEmision;
    private \DateTimeImmutable $vigenciaHasta;
    private string $moneda;
    private float $tasaCambio;
    private float $subtotalUsd;
    private float $descuentoUsd;
    private float $totalUsd;
    private string $modalidadPagoSugerida;
    private ?int $cuotasSugeridas;
    private ?string $observaciones;
    private ?string $terminosCondiciones;
    private array $items;          // [{itemable_id, itemable_type, descripcion, cantidad, precio_unitario_usd, subtotal_usd}]

    private function __construct(
        string $numero,
        int $clienteId,
        ?int $leadId,
        ?int $vendedorId,
        \DateTimeImmutable $fechaEmision,
        \DateTimeImmutable $vigenciaHasta,
        string $moneda,
        float $tasaCambio,
        float $subtotalUsd,
        float $descuentoUsd,
        string $modalidadPagoSugerida,
        ?int $cuotasSugeridas,
        ?string $observaciones,
        ?string $terminosCondiciones,
        array $items
    ) {
        $this->id = null;
        $this->numero = $numero;
        $this->clienteId = $clienteId;
        $this->leadId = $leadId;
        $this->vendedorId = $vendedorId;
        $this->estado = QuoteStatus::BORRADOR;
        $this->fechaEmision = $fechaEmision;
        $this->vigenciaHasta = $vigenciaHasta;
        $this->moneda = $moneda;
        $this->tasaCambio = $tasaCambio;
        $this->subtotalUsd = $subtotalUsd;
        $this->descuentoUsd = $descuentoUsd;
        $this->totalUsd = max(0, $subtotalUsd - $descuentoUsd);
        $this->modalidadPagoSugerida = $modalidadPagoSugerida;
        $this->cuotasSugeridas = $cuotasSugeridas;
        $this->observaciones = $observaciones;
        $this->terminosCondiciones = $terminosCondiciones;
        $this->items = $items;
    }

    public static function create(
        string $numero,
        int $clienteId,
        \DateTimeImmutable $fechaEmision,
        \DateTimeImmutable $vigenciaHasta,
        array $items,
        ?int $leadId = null,
        ?int $vendedorId = null,
        string $moneda = 'USD',
        float $tasaCambio = 1,
        float $descuentoUsd = 0,
        string $modalidadPagoSugerida = 'CONTADO',
        ?int $cuotasSugeridas = null,
        ?string $observaciones = null,
        ?string $terminosCondiciones = null,
    ): self {
        if (empty($items)) {
            throw QuoteException::noItems();
        }
        if ($vigenciaHasta < $fechaEmision) {
            throw new \InvalidArgumentException('La vigencia debe ser posterior o igual a la fecha de emisión.');
        }
        if ($descuentoUsd < 0) {
            throw new \InvalidArgumentException('El descuento no puede ser negativo.');
        }
        if (!in_array($modalidadPagoSugerida, ['CONTADO', 'CUOTAS'], true)) {
            throw new \InvalidArgumentException("Modalidad pago inválida: {$modalidadPagoSugerida}");
        }

        $subtotal = 0;
        foreach ($items as $item) {
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precio   = (float) ($item['precio_unitario_usd'] ?? 0);
            if ($cantidad <= 0 || $precio < 0) {
                throw new \InvalidArgumentException('Items con cantidad o precio inválido.');
            }
            $subtotal += round($cantidad * $precio, 4);
        }

        if ($descuentoUsd > $subtotal) {
            throw new \InvalidArgumentException('El descuento no puede superar el subtotal.');
        }

        return new self(
            $numero,
            $clienteId,
            $leadId,
            $vendedorId,
            $fechaEmision,
            $vigenciaHasta,
            strtoupper($moneda),
            $tasaCambio,
            $subtotal,
            $descuentoUsd,
            $modalidadPagoSugerida,
            $cuotasSugeridas,
            $observaciones,
            $terminosCondiciones,
            $items
        );
    }

    public function withId(int $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function marcarEnviado(): void
    {
        if (!$this->estado->puedeTransicionarA(QuoteStatus::ENVIADO)) {
            throw QuoteException::invalidTransition($this->estado, QuoteStatus::ENVIADO);
        }
        $this->estado = QuoteStatus::ENVIADO;
    }

    public function marcarAceptado(): void
    {
        if (!$this->estado->puedeTransicionarA(QuoteStatus::ACEPTADO)) {
            throw QuoteException::invalidTransition($this->estado, QuoteStatus::ACEPTADO);
        }
        $this->estado = QuoteStatus::ACEPTADO;
    }

    public function marcarRechazado(): void
    {
        if (!$this->estado->puedeTransicionarA(QuoteStatus::RECHAZADO)) {
            throw QuoteException::invalidTransition($this->estado, QuoteStatus::RECHAZADO);
        }
        $this->estado = QuoteStatus::RECHAZADO;
    }

    public function marcarVencido(): void
    {
        if (!$this->estado->puedeTransicionarA(QuoteStatus::VENCIDO)) {
            throw QuoteException::invalidTransition($this->estado, QuoteStatus::VENCIDO);
        }
        $this->estado = QuoteStatus::VENCIDO;
    }

    public function marcarConvertido(): void
    {
        if (!$this->estado->puedeTransicionarA(QuoteStatus::CONVERTIDO)) {
            throw QuoteException::invalidTransition($this->estado, QuoteStatus::CONVERTIDO);
        }
        $this->estado = QuoteStatus::CONVERTIDO;
    }

    public function estaVencido(): bool
    {
        return $this->vigenciaHasta < new \DateTimeImmutable();
    }

    public function puedeConvertirseAVenta(): bool
    {
        return $this->estado === QuoteStatus::ACEPTADO && !$this->estaVencido();
    }

    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'numero_presupuesto'     => $this->numero,
            'cliente_id'             => $this->clienteId,
            'lead_id'                => $this->leadId,
            'vendedor_id'            => $this->vendedorId,
            'estado'                 => $this->estado->value,
            'fecha_emision'          => $this->fechaEmision->format('Y-m-d'),
            'vigencia_hasta'         => $this->vigenciaHasta->format('Y-m-d'),
            'moneda'                 => $this->moneda,
            'tasa_cambio'            => $this->tasaCambio,
            'subtotal_usd'           => $this->subtotalUsd,
            'descuento_usd'          => $this->descuentoUsd,
            'total_usd'              => $this->totalUsd,
            'modalidad_pago_sugerida'=> $this->modalidadPagoSugerida,
            'cuotas_sugeridas'       => $this->cuotasSugeridas,
            'observaciones'          => $this->observaciones,
            'terminos_condiciones'   => $this->terminosCondiciones,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getNumero(): string { return $this->numero; }
    public function getClienteId(): int { return $this->clienteId; }
    public function getLeadId(): ?int { return $this->leadId; }
    public function getVendedorId(): ?int { return $this->vendedorId; }
    public function getEstado(): QuoteStatus { return $this->estado; }
    public function getTotalUsd(): float { return $this->totalUsd; }
    public function getItems(): array { return $this->items; }
}
