<?php

declare(strict_types=1);

namespace App\Domain\Customers\Aggregates;

use App\Domain\Customers\Exceptions\InvalidCustomerDataException;
use App\Domain\Customers\ValueObjects\Ruc;
use App\Domain\Customers\ValueObjects\CustomerId;
use App\Domain\Shared\ValueObjects\Money;

/**
 * Customer Aggregate Root.
 *
 * Invariantes:
 * - RUC es válido y único en el sistema.
 * - Email (si presente) es válido y único.
 * - Línea de crédito ≥ 0.
 * - Si activo = false, no se pueden crear nuevas ventas para este cliente.
 */
final class Customer
{
    private ?CustomerId $id;
    private Ruc $ruc;
    private string $razonSocial;
    private ?string $nombreFantasia;
    private string $pais;
    private ?string $email;
    private ?string $telefono;
    private ?string $direccion;
    private float $lineaCreditoUsd;
    private bool $activo;

    private function __construct(
        Ruc $ruc,
        string $razonSocial,
        ?string $nombreFantasia,
        string $pais,
        ?string $email,
        ?string $telefono,
        ?string $direccion,
        float $lineaCreditoUsd,
        bool $activo
    ) {
        $this->id = null;
        $this->ruc = $ruc;
        $this->razonSocial = $razonSocial;
        $this->nombreFantasia = $nombreFantasia;
        $this->pais = $pais;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->direccion = $direccion;
        $this->lineaCreditoUsd = $lineaCreditoUsd;
        $this->activo = $activo;
    }

    public static function create(
        Ruc $ruc,
        string $razonSocial,
        ?string $nombreFantasia,
        string $pais,
        ?string $email,
        ?string $telefono,
        ?string $direccion,
        float $lineaCreditoUsd = 0,
        bool $activo = true
    ): self {
        if (trim($razonSocial) === '') {
            throw InvalidCustomerDataException::missingRazonSocial();
        }

        if ($lineaCreditoUsd < 0) {
            throw InvalidCustomerDataException::negativeCreditLimit($lineaCreditoUsd);
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw InvalidCustomerDataException::invalidEmail($email);
        }

        return new self(
            $ruc,
            trim($razonSocial),
            $nombreFantasia ? trim($nombreFantasia) : null,
            strtoupper($pais),
            $email ? strtolower(trim($email)) : null,
            $telefono ? trim($telefono) : null,
            $direccion ? trim($direccion) : null,
            $lineaCreditoUsd,
            $activo
        );
    }

    public function withId(CustomerId $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function updateCreditLimit(float $nuevoLimite): void
    {
        if ($nuevoLimite < 0) {
            throw InvalidCustomerDataException::negativeCreditLimit($nuevoLimite);
        }
        $this->lineaCreditoUsd = $nuevoLimite;
    }

    public function deactivate(): void
    {
        $this->activo = false;
    }

    public function activate(): void
    {
        $this->activo = true;
    }

    public function ensureCanSell(): void
    {
        if (!$this->activo) {
            throw InvalidCustomerDataException::inactive($this->ruc->value());
        }
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id?->value(),
            'ruc'               => $this->ruc->value(),
            'razon_social'      => $this->razonSocial,
            'nombre_fantasia'   => $this->nombreFantasia,
            'pais'              => $this->pais,
            'email'             => $this->email,
            'telefono'          => $this->telefono,
            'direccion'         => $this->direccion,
            'linea_credito_usd' => $this->lineaCreditoUsd,
            'activo'            => $this->activo,
        ];
    }

    public function getId(): ?CustomerId { return $this->id; }
    public function getRuc(): Ruc { return $this->ruc; }
    public function getRazonSocial(): string { return $this->razonSocial; }
    public function getEmail(): ?string { return $this->email; }
    public function getLineaCreditoUsd(): float { return $this->lineaCreditoUsd; }
    public function isActivo(): bool { return $this->activo; }
}
