<?php

declare(strict_types=1);

namespace App\Application\Leads;

final class CaptureLeadDTO
{
    public function __construct(
        public readonly string  $nombre,
        public readonly string  $telefono,
        public readonly ?int    $vehiculoId = null,
        public readonly ?string $email = null,
        public readonly string  $canal = 'Formulario',
        public readonly ?string $mensaje = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre:      (string) ($data['nombre'] ?? ''),
            telefono:    (string) ($data['telefono'] ?? ''),
            vehiculoId:  isset($data['vehiculo_id']) ? (int) $data['vehiculo_id'] : null,
            email:       $data['email'] ?? null,
            canal:       (string) ($data['canal'] ?? 'Formulario'),
            mensaje:     $data['mensaje'] ?? null,
            ipAddress:   $data['ip_address'] ?? null,
            userAgent:   $data['user_agent'] ?? null,
        );
    }
}
