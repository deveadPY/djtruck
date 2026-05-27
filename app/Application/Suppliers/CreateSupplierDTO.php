<?php

declare(strict_types=1);

namespace App\Application\Suppliers;

final class CreateSupplierDTO
{
    public function __construct(
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

    public static function fromArray(array $data): self
    {
        return new self(
            razonSocial:                (string) ($data['razon_social'] ?? ''),
            rucRutNit:                  $data['ruc_rut_nit'] ?? null,
            nombreFantasia:             $data['nombre_fantasia'] ?? null,
            pais:                       (string) ($data['pais'] ?? 'PY'),
            tipo:                       (string) ($data['tipo'] ?? 'DISTRIBUIDOR'),
            monedaPrincipal:            (string) ($data['moneda_principal'] ?? 'USD'),
            diasCredito:                (int) ($data['dias_credito'] ?? 0),
            descuentoPagoAnticipadoPct: (float) ($data['descuento_pago_anticipado_pct'] ?? 0),
            email:                      $data['email'] ?? null,
            telefono:                   $data['telefono'] ?? null,
            direccion:                  $data['direccion'] ?? null,
            ciudad:                     $data['ciudad'] ?? null,
            sitioWeb:                   $data['sitio_web'] ?? null,
            contactoPrincipal:          $data['contacto_principal'] ?? null,
            banco:                      $data['banco'] ?? null,
            cuentaBancaria:             $data['cuenta_bancaria'] ?? null,
            observaciones:              $data['observaciones'] ?? null,
            activo:                     (bool) ($data['activo'] ?? true),
        );
    }
}
