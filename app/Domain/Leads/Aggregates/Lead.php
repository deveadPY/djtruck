<?php

declare(strict_types=1);

namespace App\Domain\Leads\Aggregates;

use App\Domain\Leads\Exceptions\InvalidLeadTransitionException;
use App\Domain\Leads\ValueObjects\LeadId;
use App\Domain\Leads\ValueObjects\LeadStatus;

/**
 * Lead Aggregate Root — consulta desde el catálogo web.
 *
 * Pipeline:
 *   NUEVO → asignado a vendedor → CONTACTADO → CERRADO (venta) / PERDIDO
 *
 * Invariantes:
 * - Nombre y teléfono obligatorios.
 * - Estado sigue máquina de estados (LeadStatus::puedeTransicionarA).
 * - Si CERRADO, debe tener venta_id.
 * - Si PERDIDO, debe tener motivo_perdido.
 */
final class Lead
{
    private ?LeadId $id;
    private ?int $vehiculoId;
    private string $nombre;
    private string $telefono;
    private ?string $email;
    private string $canal;
    private LeadStatus $estado;
    private ?string $mensaje;
    private ?int $asignadoA;
    private ?\DateTimeImmutable $asignadoEn;
    private ?\DateTimeImmutable $contactadoEn;
    private ?int $ventaId;
    private ?string $motivoPerdido;
    private ?string $notasInternas;

    private function __construct(
        ?int $vehiculoId,
        string $nombre,
        string $telefono,
        ?string $email,
        string $canal,
        LeadStatus $estado,
        ?string $mensaje
    ) {
        $this->id = null;
        $this->vehiculoId = $vehiculoId;
        $this->nombre = $nombre;
        $this->telefono = $telefono;
        $this->email = $email;
        $this->canal = $canal;
        $this->estado = $estado;
        $this->mensaje = $mensaje;
        $this->asignadoA = null;
        $this->asignadoEn = null;
        $this->contactadoEn = null;
        $this->ventaId = null;
        $this->motivoPerdido = null;
        $this->notasInternas = null;
    }

    public static function capture(
        ?int $vehiculoId,
        string $nombre,
        string $telefono,
        ?string $email = null,
        string $canal = 'Formulario',
        ?string $mensaje = null
    ): self {
        if (trim($nombre) === '') {
            throw new \InvalidArgumentException('El nombre del contacto es obligatorio.');
        }
        if (trim($telefono) === '') {
            throw new \InvalidArgumentException('El teléfono de contacto es obligatorio.');
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Email inválido: {$email}");
        }
        if (!in_array($canal, ['WhatsApp', 'Formulario'], true)) {
            throw new \InvalidArgumentException("Canal inválido: {$canal}");
        }

        return new self(
            $vehiculoId,
            trim($nombre),
            trim($telefono),
            $email ? strtolower(trim($email)) : null,
            $canal,
            LeadStatus::NUEVO,
            $mensaje ? trim($mensaje) : null
        );
    }

    public function withId(LeadId $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function asignarA(int $userId): void
    {
        if ($this->estado->esTerminal()) {
            throw InvalidLeadTransitionException::alreadyTerminal($this->estado);
        }
        $this->asignadoA = $userId;
        $this->asignadoEn = new \DateTimeImmutable();
    }

    public function marcarContactado(): void
    {
        if (!$this->estado->puedeTransicionarA(LeadStatus::CONTACTADO)) {
            throw InvalidLeadTransitionException::notAllowed($this->estado, LeadStatus::CONTACTADO);
        }
        $this->estado = LeadStatus::CONTACTADO;
        $this->contactadoEn = new \DateTimeImmutable();
    }

    public function cerrarConVenta(int $ventaId): void
    {
        if (!$this->estado->puedeTransicionarA(LeadStatus::CERRADO)) {
            throw InvalidLeadTransitionException::notAllowed($this->estado, LeadStatus::CERRADO);
        }
        $this->estado = LeadStatus::CERRADO;
        $this->ventaId = $ventaId;
    }

    public function marcarPerdido(string $motivo): void
    {
        if (!$this->estado->puedeTransicionarA(LeadStatus::PERDIDO)) {
            throw InvalidLeadTransitionException::notAllowed($this->estado, LeadStatus::PERDIDO);
        }
        if (trim($motivo) === '') {
            throw new \InvalidArgumentException('Debe indicar el motivo por el que se pierde el lead.');
        }
        $this->estado = LeadStatus::PERDIDO;
        $this->motivoPerdido = trim($motivo);
    }

    public function actualizarNotas(string $notas): void
    {
        $this->notasInternas = $notas;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id?->value(),
            'vehiculo_id'     => $this->vehiculoId,
            'nombre'          => $this->nombre,
            'telefono'        => $this->telefono,
            'email'           => $this->email,
            'canal'           => $this->canal,
            'estado'          => $this->estado->value,
            'mensaje'         => $this->mensaje,
            'asignado_a'      => $this->asignadoA,
            'asignado_en'     => $this->asignadoEn?->format('Y-m-d H:i:s'),
            'contactado_en'   => $this->contactadoEn?->format('Y-m-d H:i:s'),
            'venta_id'        => $this->ventaId,
            'motivo_perdido'  => $this->motivoPerdido,
            'notas_internas'  => $this->notasInternas,
        ];
    }

    public function getId(): ?LeadId { return $this->id; }
    public function getEstado(): LeadStatus { return $this->estado; }
    public function getAsignadoA(): ?int { return $this->asignadoA; }
    public function getVehiculoId(): ?int { return $this->vehiculoId; }
}
