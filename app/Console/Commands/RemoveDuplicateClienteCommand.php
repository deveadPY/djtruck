<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Soft-delete (reversible) de un cliente duplicado.
 *
 * Uso:
 *   php artisan clientes:remove-duplicate 2
 *
 * Verifica que el cliente no tenga ventas/planes activos antes de eliminar
 * (por seguridad) y pide confirmación.
 */
class RemoveDuplicateClienteCommand extends Command
{
    protected $signature = 'clientes:remove-duplicate {id : ID del cliente a eliminar (soft-delete)} {--force : Forzar eliminación aunque tenga datos asociados}';

    protected $description = 'Soft-delete (reversible) de un cliente duplicado';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $force = $this->option('force');

        $cli = DB::table('clientes')->where('id', $id)->first();

        if (!$cli) {
            $this->error("Cliente #{$id} no encontrado.");
            return self::FAILURE;
        }

        if ($cli->deleted_at !== null) {
            $this->warn("Cliente #{$id} ya estaba eliminado (deleted_at: {$cli->deleted_at}).");
            return self::SUCCESS;
        }

        // Contar referencias
        $ventas = DB::table('ventas')->where('cliente_id', $id)->whereNull('deleted_at')->count();
        $planes = DB::table('planes_cuotas')->where('cliente_id', $id)->whereNull('deleted_at')->count();
        $cuotasPendientes = DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->where('planes_cuotas.cliente_id', $id)
            ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA'])
            ->whereNull('cuotas.deleted_at')
            ->count();

        $this->info('Cliente a eliminar:');
        $this->line("  ID: #{$id}");
        $this->line("  Razón social: {$cli->razon_social}");
        $this->line("  RUC: " . ($cli->ruc ?? '-'));
        $this->line("  Email: " . ($cli->email ?? '-'));
        $this->line("  Ventas activas: {$ventas}");
        $this->line("  Planes activos: {$planes}");
        $this->line("  Cuotas pendientes: {$cuotasPendientes}");

        if (($ventas > 0 || $planes > 0 || $cuotasPendientes > 0) && !$force) {
            $this->error('❌ Este cliente tiene datos asociados. No se elimina por seguridad.');
            $this->comment('Si estás seguro, usá: php artisan clientes:remove-duplicate ' . $id . ' --force');
            return self::FAILURE;
        }

        if (!$this->confirm('¿Confirmar soft-delete (reversible)?')) {
            $this->line('Cancelado.');
            return self::SUCCESS;
        }

        DB::table('clientes')->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✅ Cliente #{$id} eliminado (soft-delete).");
        $this->comment('Para revertir: UPDATE clientes SET deleted_at=NULL WHERE id=' . $id);

        return self::SUCCESS;
    }
}
