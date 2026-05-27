<?php

declare(strict_types=1);

namespace App\Application\Suppliers;

final class UpdateSupplierDTO
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $razonSocial,
        public readonly ?string $rucRutNit = null,
        public readonly ?string $nombreFantasia = null,
        public readonly string  $pais = 'PY',
        public readonly string  $tipo = 'DISTRIBUIDOR',
        public readonly string  $monedaPrincipal = 'USD',
        public readonly int     $diasCredito = 0,
        public readonly float   $descuentoPagoAnticipadoPct = 0,
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly ?string $ciudad = null,
        public readonly ?string $sitioWeb = null,
        public readonly ?string $contactoPrincipal = null,
        public readonly ?string $banco = null,
        public readonly ?string $cuentaBancaria = null,
        public readonly ?string $observaciones = null,
        public readonly bool    $activo = true,
    ) {}
}
