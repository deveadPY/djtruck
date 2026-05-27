<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exceptions;

use RuntimeException;

class InsufficientCreditLimitException extends RuntimeException
{
    public readonly int $clienteId;
    public readonly float $capitalRequerido;
    public readonly float $creditoDisponible;

    public function __construct(int $clienteId, float $capitalRequerido, float $creditoDisponible)
    {
        $this->clienteId = $clienteId;
        $this->capitalRequerido = $capitalRequerido;
        $this->creditoDisponible = $creditoDisponible;

        parent::__construct(sprintf(
            'El capital a financiar (USD %.2f) supera la línea de crédito disponible del cliente (USD %.2f).',
            $capitalRequerido,
            max(0, $creditoDisponible)
        ));
    }
}
