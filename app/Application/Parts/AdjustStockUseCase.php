<?php

declare(strict_types=1);

namespace App\Application\Parts;

use App\Domain\Parts\Events\LowStockAlert;
use App\Domain\Parts\Events\StockAdjusted;
use App\Domain\Parts\Repositories\PartRepositoryInterface;
use App\Domain\Parts\Services\StockMovementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;

final class AdjustStockUseCase
{
    private const MOTIVOS_VALIDOS = [
        'MERMA', 'ROBO', 'DAÑO', 'AJUSTE_INVENTARIO',
        'DEVOLUCION_CLIENTE', 'DEVOLUCION_PROVEEDOR', 'TRANSFERENCIA', 'OTRO',
    ];

    public function __construct(
        private readonly PartRepositoryInterface $repository,
        private readonly StockMovementService $kardex,
    ) {}

    public function execute(AdjustStockDTO $dto): void
    {
        if (!in_array($dto->motivo, self::MOTIVOS_VALIDOS, true)) {
            throw new RuntimeException("Motivo inválido: {$dto->motivo}");
        }

        $part = $this->repository->findById($dto->partId);
        if (!$part) {
            throw new RuntimeException("Repuesto {$dto->partId} no encontrado.");
        }

        $stockAnterior = $part->getStock()->actual;
        $part->ajustarStock($dto->nuevaCantidad);

        DB::transaction(function () use ($dto, $part, $stockAnterior) {
            $this->repository->update($dto->partId, $part);

            $diferencia = $dto->nuevaCantidad - $stockAnterior;
            $this->kardex->registrar(
                partId:                  $dto->partId,
                tipo:                    StockMovementService::TIPO_AJUSTE,
                motivo:                  $dto->motivo,
                cantidad:                abs($diferencia),
                saldoResultante:         $dto->nuevaCantidad,
                costoUnitarioUsd:        $part->getCostoPromedioUsd(),
                costoPromedioResultante: $part->getCostoPromedioUsd(),
                observaciones:           $dto->observaciones,
            );
        });

        Event::dispatch(new StockAdjusted(
            partId:        $dto->partId,
            codigo:        $part->getCodigo()->value(),
            stockAnterior: $stockAnterior,
            stockNuevo:    $dto->nuevaCantidad,
            motivo:        $dto->motivo,
            userId:        Auth::id(),
        ));

        // Si quedó bajo mínimo, disparar alerta
        if ($part->getStock()->bajoMinimo()) {
            Event::dispatch(new LowStockAlert(
                partId:      $dto->partId,
                codigo:      $part->getCodigo()->value(),
                descripcion: 'Stock bajo después de ajuste',
                stockActual: $part->getStock()->actual,
                stockMinimo: $part->getStock()->minimo,
            ));
        }
    }
}
