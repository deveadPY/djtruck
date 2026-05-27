<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Domain\Quotes\Aggregates\Quote;
use App\Domain\Quotes\Events\QuoteCreated;
use App\Domain\Quotes\Repositories\QuoteRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class CreateQuoteUseCase
{
    public function __construct(
        private readonly QuoteRepositoryInterface $repository,
    ) {}

    public function execute(CreateQuoteDTO $dto): Quote
    {
        $numero = $this->repository->nextNumero();

        $quote = Quote::create(
            numero:                $numero,
            clienteId:             $dto->clienteId,
            fechaEmision:          new \DateTimeImmutable($dto->fechaEmision),
            vigenciaHasta:         new \DateTimeImmutable($dto->vigenciaHasta),
            items:                 $dto->items,
            leadId:                $dto->leadId,
            vendedorId:            $dto->vendedorId,
            moneda:                $dto->moneda,
            tasaCambio:            $dto->tasaCambio,
            descuentoUsd:          $dto->descuentoUsd,
            modalidadPagoSugerida: $dto->modalidadPagoSugerida,
            cuotasSugeridas:       $dto->cuotasSugeridas,
            observaciones:         $dto->observaciones,
            terminosCondiciones:   $dto->terminosCondiciones,
        );

        $saved = DB::transaction(fn() => $this->repository->save($quote));

        Event::dispatch(new QuoteCreated(
            quoteId:   $saved->getId(),
            numero:    $saved->getNumero(),
            clienteId: $saved->getClienteId(),
            totalUsd:  $saved->getTotalUsd(),
        ));

        return $saved;
    }
}
