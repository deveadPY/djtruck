<?php

declare(strict_types=1);

namespace App\Domain\Parts\Exceptions;

use RuntimeException;

class InsufficientPartStockException extends RuntimeException
{
    public readonly int $partId;
    public readonly float $solicitado;
    public readonly float $disponible;

    public function __construct(int $partId, string $codigo, float $solicitado, float $disponible)
    {
        $this->partId = $partId;
        $this->solicitado = $solicitado;
        $this->disponible = $disponible;
        parent::__construct(sprintf(
            'Stock insuficiente para repuesto %s. Solicitado: %.3f, disponible: %.3f.',
            $codigo,
            $solicitado,
            $disponible
        ));
    }
}
