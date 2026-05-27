<?php

declare(strict_types=1);

namespace App\Application\Quotes;

final class CreateQuoteDTO
{
    public function __construct(
        public readonly int     $clienteId,
        public readonly string  $fechaEmision,        // YYYY-MM-DD
        public readonly string  $vigenciaHasta,       // YYYY-MM-DD
        public readonly array   $items,               // [{itemable_id, itemable_type, descripcion, cantidad, precio_unitario_usd}]
        public readonly ?int    $leadId = null,
        public readonly ?int    $vendedorId = null,
        public readonly string  $moneda = 'USD',
        public readonly float   $tasaCambio = 1,
        public readonly float   $descuentoUsd = 0,
        public readonly string  $modalidadPagoSugerida = 'CONTADO',
        public readonly ?int    $cuotasSugeridas = null,
        public readonly ?string $observaciones = null,
        public readonly ?string $terminosCondiciones = null,
    ) {}
}
