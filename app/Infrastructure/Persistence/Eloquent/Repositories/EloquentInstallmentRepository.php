<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Sales\Repositories\InstallmentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentInstallmentRepository implements InstallmentRepositoryInterface
{
    public function insertMany(array $cuotas): void
    {
        if (empty($cuotas)) {
            return;
        }
        DB::table('cuotas')->insert($cuotas);
    }

    public function getByPlan(int $planId): Collection
    {
        return collect(
            DB::table('cuotas')
                ->where('plan_cuotas_id', $planId)
                ->whereNull('deleted_at')
                ->orderBy('numero_cuota')
                ->get()
        );
    }

    public function getByVenta(int $ventaId): Collection
    {
        return collect(
            DB::table('cuotas')
                ->where('venta_id', $ventaId)
                ->whereNull('deleted_at')
                ->orderBy('numero_cuota')
                ->get()
        );
    }

    public function findById(int $id): ?object
    {
        return DB::table('cuotas')->where('id', $id)->first();
    }

    public function update(int $id, array $data): bool
    {
        return DB::table('cuotas')
            ->where('id', $id)
            ->update(array_merge($data, ['updated_at' => now()])) > 0;
    }

    public function deleteByPlan(int $planId): void
    {
        DB::table('cuotas')->where('plan_cuotas_id', $planId)->delete();
    }

    public function getOverdue(): Collection
    {
        return collect(
            DB::table('cuotas')
                ->where('fecha_vencimiento', '<', now()->toDateString())
                ->whereIn('estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
                ->whereNull('deleted_at')
                ->get()
        );
    }

    public function getDueToday(): Collection
    {
        return collect(
            DB::table('cuotas')
                ->where('fecha_vencimiento', now()->toDateString())
                ->whereIn('estado', ['PENDIENTE', 'PAGADA_PARCIAL'])
                ->whereNull('deleted_at')
                ->get()
        );
    }
}
