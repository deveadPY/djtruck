<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use App\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SaleApplicationService
{
    public function __construct(
        private readonly CreateSaleUseCase $createSaleUseCase,
        private readonly UpdateSaleUseCase $updateSaleUseCase,
        private readonly CancelSaleUseCase $cancelSaleUseCase,
        private readonly SaleRepositoryInterface $saleRepository
    ) {}

    public function create(CreateSaleDTO $dto): SaleModel
    {
        return $this->createSaleUseCase->execute($dto);
    }

    public function update(UpdateSaleDTO $dto): bool
    {
        return $this->updateSaleUseCase->execute($dto);
    }

    public function cancel(CancelSaleDTO $dto): bool
    {
        return $this->cancelSaleUseCase->execute($dto);
    }

    public function findById(int $id): ?SaleModel
    {
        return $this->saleRepository->findById($id);
    }

    public function findByIdWithTrashed(int $id): ?SaleModel
    {
        return $this->saleRepository->findByIdWithTrashed($id);
    }

    public function getLatest(int $limit = 20): LengthAwarePaginator
    {
        return $this->saleRepository->getLatest($limit);
    }

    public function getItems(int $saleId): Collection
    {
        return $this->saleRepository->getItems($saleId);
    }

    public function getPayments(int $saleId): Collection
    {
        return $this->saleRepository->getPayments($saleId);
    }

    public function getInstallmentPlan(int $saleId): ?object
    {
        return $this->saleRepository->getPlan($saleId);
    }

    public function getDocuments(int $saleId): Collection
    {
        return $this->saleRepository->getDocuments($saleId);
    }

    /**
     * Calcula la rentabilidad neta de una venta:
     * precio final (con descuento) menos el valor en libros del item principal.
     */
    public function calculateRentability(SaleModel $venta): float
    {
        $precioFinalUsd = (float) $venta->precio_venta_usd - (float) ($venta->descuento_usd ?? 0);
        return $precioFinalUsd - (float) ($venta->valor_libro_snapshot ?? 0);
    }

    /**
     * Recupera la información de cancelación de una venta desde audit_logs.
     * Devuelve motivo, fecha, usuario e IP, o null si la venta no fue cancelada.
     */
    public function getCancellationInfo(int $saleId): ?object
    {
        $log = AuditLogModel::where('entity_type', 'venta')
            ->where('entity_id', $saleId)
            ->where('action', 'CANCEL_SALE')
            ->latest('created_at')
            ->first();

        if (!$log) {
            return null;
        }

        $newValues = $log->new_values ?? [];

        return (object) [
            'motivo'  => $newValues['motivo'] ?? null,
            'fecha'   => $log->created_at,
            'usuario' => optional(User::find($log->user_id))->name,
            'ip'      => $log->ip_address,
        ];
    }
}
