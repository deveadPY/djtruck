<?php

declare(strict_types=1);

namespace App\Domain\Commissions\Events\Listeners;

use App\Application\Commissions\CalculateCommissionUseCase;
use App\Domain\Sales\Events\SaleCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Cuando una venta se completa, calcula automáticamente la comisión del vendedor.
 * No bloquea el flujo de venta si falla — solo loguea.
 */
class CalculateCommissionOnSaleCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly CalculateCommissionUseCase $useCase,
    ) {}

    public function handle(SaleCompleted $event): void
    {
        try {
            $comisionId = $this->useCase->execute($event->saleId);
            if ($comisionId !== null) {
                Log::info('commission.calculated', [
                    'venta_id'    => $event->saleId,
                    'comision_id' => $comisionId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('commission.calc_failed', [
                'venta_id' => $event->saleId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
