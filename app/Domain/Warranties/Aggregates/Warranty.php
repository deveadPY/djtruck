<?php

declare(strict_types=1);

namespace App\Domain\Warranties\Aggregates;

use App\Domain\Warranties\Exceptions\WarrantyException;
use App\Domain\Warranties\ValueObjects\WarrantyStatus;

/**
 * Warranty Aggregate Root — garantía emitida con una venta.
 *
 * Invariantes:
 * - vencimiento > inicio.
 * - Si km_limite no es null, debe ser > km_inicio.
 * - Estado consistente con fechas/kilometraje.
 */
final class Warranty
{
    private ?int $id;
    private int $ventaId;
    private ?int $vehiculoId;
    private ?int $repuestoId;
    private string $tipo;
    private \DateTimeImmutable $inicio;
    private \DateTimeImmutable $vencimiento;
    private ?int $kmInicio;
    private ?int $kmLimite;
    private ?string $cobertura;
    private ?string $exclusiones;
    private WarrantyStatus $estado;

    private function __construct(
        int $ventaId,
        ?int $vehiculoId,
        ?int $repuestoId,
        string $tipo,
        \DateTimeImmutable $inicio,
        \DateTimeImmutable $vencimiento,
        ?int $kmInicio,
        ?int $kmLimite,
        ?string $cobertura,
        ?string $exclusiones
    ) {
        $this->id = null;
        $this->ventaId = $ventaId;
        $this->vehiculoId = $vehiculoId;
        $this->repuestoId = $repuestoId;
        $this->tipo = $tipo;
        $this->inicio = $inicio;
        $this->vencimiento = $vencimiento;
        $this->kmInicio = $kmInicio;
        $this->kmLimite = $kmLimite;
        $this->cobertura = $cobertura;
        $this->exclusiones = $exclusiones;
        $this->estado = WarrantyStatus::VIGENTE;
    }

    public static function create(
        int $ventaId,
        \DateTimeImmutable $inicio,
        \DateTimeImmutable $vencimiento,
        string $tipo = 'FABRICA',
        ?int $vehiculoId = null,
        ?int $repuestoId = null,
        ?int $kmInicio = null,
        ?int $kmLimite = null,
        ?string $cobertura = null,
        ?string $exclusiones = null,
    ): self {
        if ($vencimiento <= $inicio) {
            throw WarrantyException::invalidDateRange();
        }
        if ($vehiculoId === null && $repuestoId === null) {
            throw new \InvalidArgumentException('La garantía debe estar asociada a un vehículo o repuesto.');
        }
        if (!in_array($tipo, ['FABRICA', 'EXTENDIDA', 'TALLER', 'OTRA'], true)) {
            throw new \InvalidArgumentException("Tipo inválido: {$tipo}");
        }
        if ($kmLimite !== null && $kmInicio !== null && $kmLimite <= $kmInicio) {
            throw new \InvalidArgumentException('El km límite debe ser superior al km inicial.');
        }

        return new self(
            $ventaId, $vehiculoId, $repuestoId, $tipo,
            $inicio, $vencimiento, $kmInicio, $kmLimite, $cobertura, $exclusiones
        );
    }

    public function withId(int $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function ensureVigente(): void
    {
        if (!$this->estado->permiteReclamo()) {
            throw WarrantyException::notVigente($this->id ?? 0, $this->estado->value);
        }
        if ($this->vencimiento < new \DateTimeImmutable()) {
            throw WarrantyException::expired($this->vencimiento->format('Y-m-d'));
        }
    }

    public function marcarVencida(): void { $this->estado = WarrantyStatus::VENCIDA; }
    public function marcarAgotadaKm(): void { $this->estado = WarrantyStatus::AGOTADA_KM; }
    public function anular(): void { $this->estado = WarrantyStatus::ANULADA; }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'venta_id'     => $this->ventaId,
            'vehiculo_id'  => $this->vehiculoId,
            'repuesto_id'  => $this->repuestoId,
            'tipo'         => $this->tipo,
            'inicio'       => $this->inicio->format('Y-m-d'),
            'vencimiento'  => $this->vencimiento->format('Y-m-d'),
            'km_inicio'    => $this->kmInicio,
            'km_limite'    => $this->kmLimite,
            'cobertura'    => $this->cobertura,
            'exclusiones'  => $this->exclusiones,
            'estado'       => $this->estado->value,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getEstado(): WarrantyStatus { return $this->estado; }
}
