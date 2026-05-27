<?php

declare(strict_types=1);

namespace App\Application\Customers;

final class UpdateCustomerDTO
{
    public function __construct(
        public readonly int     $id,
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
}
