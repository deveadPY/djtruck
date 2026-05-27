<?php

declare(strict_types=1);

namespace App\Application\Parts;

use App\Domain\Parts\Aggregates\Part;
use App\Domain\Parts\Events\PartCreated;
use App\Domain\Parts\Repositories\PartRepositoryInterface;
use App\Domain\Parts\Services\StockMovementService;
use App\Domain\Parts\Validators\UniquePartCodeValidator;
use App\Domain\Parts\ValueObjects\PartCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class CreatePartUseCase
{
    public function __construct(
        private readonly PartRepositoryInterface $repository,
        private readonly UniquePartCodeValidator $codeValidator,
        private readonly StockMovementService $kardex,
    ) {}

    public function execute(CreatePartDTO $dto): Part
    {
        $codigo = PartCode::parse($dto->codigo);
        $this->codeValidator->validate($codigo->value());

        $part = Part::create(
            codigo:           $codigo,
            descripcion:      $dto->descripcion,
            unidadMedida:     $dto->unidadMedida,
            codigoBarras:     $dto->codigoBarras,
            marcaCompatible:  $dto->marcaCompatible,
            categoriaId:      $dto->categoriaId,
            ubicacionId:      $dto->ubicacionId,
            stockInicial:     $dto->stockInicial,
            stockMinimo:      $dto->stockMinimo,
            costoPromedioUsd: $dto->costoPromedioUsd,
            precioVentaUsd:   $dto->precioVentaUsd,
            proveedorId:      $dto->proveedorId,
            activo:           $dto->activo,
        );

        $saved = DB::transaction(function () use ($part, $dto) {
            $saved = $this->repository->save($part);

            // Si stock inicial > 0, registrar entrada en kardex
            if ($dto->stockInicial > 0 && $saved->getId() !== null) {
                $this->kardex->registrar(
                    partId:                  $saved->getId()->value(),
                    tipo:                    StockMovementService::TIPO_ENTRADA,
                    motivo:                  'AJUSTE_INVENTARIO',
                    cantidad:                $dto->stockInicial,
                    saldoResultante:         $dto->stockInicial,
                    costoUnitarioUsd:        $dto->costoPromedioUsd,
                    costoPromedioResultante: $dto->costoPromedioUsd,
                    observaciones:           'Stock inicial al crear repuesto',
                );
            }

            return $saved;
        });

        Event::dispatch(new PartCreated(
            partId:       $saved->getId()->value(),
            codigo:       $saved->getCodigo()->value(),
            stockInicial: $dto->stockInicial,
        ));

        return $saved;
    }
}
