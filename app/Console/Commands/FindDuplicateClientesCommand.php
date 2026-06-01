<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lista clientes duplicados por RUC y email con datos asociados.
 *
 * Uso:
 *   php artisan clientes:find-duplicates
 */
class FindDuplicateClientesCommand extends Command
{
    protected $signature = 'clientes:find-duplicates';

    protected $description = 'Lista clientes duplicados por RUC y email con sus datos asociados';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  DUPLICADOS POR RUC');
        $this->info('═══════════════════════════════════════════════════════════');

        $dupRuc = DB::table('clientes')
            ->select('ruc', DB::raw('COUNT(*) as cant'), DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
            ->whereNotNull('ruc')
            ->where('ruc', '!=', '')
            ->whereNull('deleted_at')
            ->groupBy('ruc')
            ->having('cant', '>', 1)
            ->get();

        if ($dupRuc->isEmpty()) {
            $this->line('  ✓ No hay duplicados por RUC');
        } else {
            foreach ($dupRuc as $d) {
                $this->newLine();
                $this->warn("RUC: {$d->ruc} ({$d->cant} registros)");
                $ids = explode(',', $d->ids);
                $rows = [];
                foreach ($ids as $id) {
                    $cli = DB::table('clientes')->where('id', $id)->first();
                    $ventas = DB::table('ventas')->where('cliente_id', $id)->whereNull('deleted_at')->count();
                    $planes = DB::table('planes_cuotas')->where('cliente_id', $id)->whereNull('deleted_at')->count();
                    $docs   = DB::table('documentos')
                        ->where('documentable_type', 'clientes')
                        ->where('documentable_id', $id)
                        ->whereNull('deleted_at')->count();

                    $rows[] = [
                        $id,
                        substr($cli->razon_social, 0, 30),
                        $cli->email ?? '-',
                        $ventas,
                        $planes,
                        $docs,
                        substr((string) $cli->created_at, 0, 10),
                    ];
                }
                $this->table(['ID', 'Razón Social', 'Email', 'Ventas', 'Planes', 'Docs', 'Creado'], $rows);
            }
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  DUPLICADOS POR EMAIL');
        $this->info('═══════════════════════════════════════════════════════════');

        $dupEmail = DB::table('clientes')
            ->select('email', DB::raw('COUNT(*) as cant'), DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNull('deleted_at')
            ->groupBy('email')
            ->having('cant', '>', 1)
            ->get();

        if ($dupEmail->isEmpty()) {
            $this->line('  ✓ No hay duplicados por email');
        } else {
            foreach ($dupEmail as $d) {
                $this->newLine();
                $this->warn("Email: {$d->email} ({$d->cant} registros)");
                $ids = explode(',', $d->ids);
                $rows = [];
                foreach ($ids as $id) {
                    $cli = DB::table('clientes')->where('id', $id)->first();
                    $rows[] = [
                        $id,
                        substr($cli->razon_social, 0, 40),
                        $cli->ruc ?? '-',
                        substr((string) $cli->created_at, 0, 10),
                    ];
                }
                $this->table(['ID', 'Razón Social', 'RUC', 'Creado'], $rows);
            }
        }

        $this->newLine();
        $this->info('Para eliminar un duplicado (soft-delete):');
        $this->comment('  php artisan clientes:remove-duplicate {ID}');

        return self::SUCCESS;
    }
}
