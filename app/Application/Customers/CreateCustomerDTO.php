<?php

declare(strict_types=1);

namespace App\Application\Customers;

final class CreateCustomerDTO
{
    public function __construct(
        public readonly string  $ruc,
        public readonly string  $razonSocial,
        public readonly ?string $nombreFantasia = null,
        public readonly string  $pais = 'PY',
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly float   $lineaCreditoUsd = 0,
        public readonly bool    $activo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ruc:             (string) ($data['ruc'] ?? ''),
            razonSocial:     (string) ($data['razon_social'] ?? ''),
            nombreFantasia:  $data['nombre_fantasia'] ?? null,
            pais:            (string) ($data['pais'] ?? 'PY'),
            email:           $data['email'] ?? null,
            telefono:        $data['telefono'] ?? null,
            direccion:       $data['direccion'] ?? null,
            lineaCreditoUsd: (float) ($data['linea_credito_usd'] ?? 0),
            activo:          (bool) ($data['activo'] ?? true),
        );
    }
}
