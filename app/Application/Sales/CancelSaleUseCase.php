<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CancelSaleUseCase
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(CancelSaleDTO $dto): bool
    {
        $sale = $this->saleRepository->findById($dto->id);
        if (!$sale || $sale->deleted_at) {
            throw new RuntimeException('La venta no existe o ya fue cancelada.');
        }

        $oldValues = $sale->toArray();

        return DB::transaction(function () use ($dto, $sale, $oldValues) {
            $ventaId = $dto->id;

            // 1. Revertir items: devolver vehículos a DISPONIBLE, retornar stock (solo items activos)
            $items = DB::table('venta_items')->where('venta_id', $ventaId)->whereNull('deleted_at')->get();
            foreach ($items as $item) {
                if (str_contains($item->itemable_type, 'Vehicle')) {
                    DB::table('vehiculos')->where('id', $item->itemable_id)->update([
                        'estado'     => 'DISPONIBLE',
                        'updated_at' => now(),
                    ]);
                }

                if (str_contains($item->itemable_type, 'Repuesto')) {
                    DB::table('stock_repuestos')->where('id', $item->itemable_id)->increment('stock_actual', $item->cantidad);
                }
            }

            // 2. Anular movimientos de caja de esta venta
            DB::table('movimientos_caja')
                ->where('ref_type', 'venta')
                ->where('referencia_id', $ventaId)
                ->update([
                    'deleted_at' => now(),
                    'concepto'   => DB::raw("CONCAT('[ANULADO] ', concepto)"),
                    'updated_at' => now(),
                ]);

            // También anular movimientos de caja de recibos de cuota
            $recibosIds = DB::table('recibos_cuota')->where('venta_id', $ventaId)->pluck('id');
            if ($recibosIds->isNotEmpty()) {
                DB::table('movimientos_caja')
                    ->where('ref_type', 'recibo_cuota')
                    ->whereIn('referencia_id', $recibosIds->toArray())
                    ->update([
                        'deleted_at' => now(),
                        'concepto'   => DB::raw("CONCAT('[ANULADO POR CANCELACIÓN VENTA] ', concepto)"),
                        'updated_at' => now(),
                    ]);
            }

            // 3. Desmarcar vehículos de canje
            DB::table('detalles_pago')
                ->where('venta_id', $ventaId)
                ->where('tipo_pago', 'VEHICULO_CANJE')
                ->whereNotNull('vehiculo_canje_id')
                ->pluck('vehiculo_canje_id')
                ->each(fn($vehiculoId) =>
                    DB::table('vehiculos')->where('id', $vehiculoId)->update([
                        'estado'     => 'DISPONIBLE',
                        'updated_at' => now(),
                    ])
                );

            // 4. Anular detalles de pago
            DB::table('detalles_pago')
                ->where('venta_id', $ventaId)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            // 5. Cancelar plan de cuotas y cuotas asociadas
            $plan = DB::table('planes_cuotas')->where('venta_id', $ventaId)->first();
            if ($plan) {
                DB::table('cuotas')
                    ->where('plan_cuotas_id', $plan->id)
                    ->update([
                        'estado'     => 'CANCELADA',
                        'deleted_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('planes_cuotas')->where('id', $plan->id)->update([
                    'estado'     => 'CANCELADO',
                    'updated_at' => now(),
                ]);
            }

            // 6. Soft delete de la venta
            $this->saleRepository->update($ventaId, [
                'estado'     => 'CANCELADO',
                'deleted_at' => now(),
                'updated_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            // 7. Auditoría
            $motivo = $dto->motivo ? " — Motivo: {$dto->motivo}" : '';
            $this->auditar(
                'CANCEL_SALE',
                'venta',
                $ventaId,
                $oldValues,
                ['estado' => 'CANCELADO', 'deleted_at' => now()->toDateTimeString(), 'motivo' => $dto->motivo],
                Auth::id(),
                request()->ip(),
            );

            return true;
        });
    }

    private function auditar(
        string $action,
        string $entityType,
        int $entityId,
        array $oldValues,
        array $newValues,
        ?int $userId,
        ?string $ipAddress,
    ): void {
        DB::table('audit_logs')->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => json_encode($oldValues),
            'new_values'  => json_encode($newValues),
            'metadata'    => null,
            'ip_address'  => $ipAddress,
            'created_at'  => now(),
        ]);
    }
}
