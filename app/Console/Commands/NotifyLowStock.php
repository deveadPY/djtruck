<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyLowStock extends Command
{
    protected $signature   = 'erp:notify-low-stock';
    protected $description = 'Detecta repuestos con stock por debajo del mínimo y registra alertas';

    public function handle(): int
    {
        $this->info('Revisando stock mínimo de repuestos...');

        $repuestosBajos = DB::table('stock_repuestos')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->whereRaw('stock_actual <= stock_minimo')
            ->get();

        if ($repuestosBajos->isEmpty()) {
            $this->info('Todos los repuestos tienen stock suficiente.');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  {$repuestosBajos->count()} repuesto(s) con stock bajo mínimo:");

        foreach ($repuestosBajos as $r) {
            $this->line("   [{$r->codigo}] {$r->descripcion} — Actual: {$r->stock_actual} / Mínimo: {$r->stock_minimo}");

            // Registrar como notificación del sistema (cuota_id = null, usuario = null)
            $yaRegistradaHoy = DB::table('notificaciones_enviadas')
                ->whereNull('cuota_id')
                ->where('asunto', 'LIKE', "%{$r->codigo}%")
                ->whereDate('enviada_en', now()->toDateString())
                ->exists();

            if (!$yaRegistradaHoy) {
                DB::table('notificaciones_enviadas')->insert([
                    'usuario_id'  => null,
                    'cuota_id'    => null,
                    'asunto'      => "Stock bajo mínimo: [{$r->codigo}] {$r->descripcion}",
                    'contenido'   => "El repuesto [{$r->codigo}] {$r->descripcion} tiene stock actual de {$r->stock_actual} " .
                                     "unidades, por debajo del mínimo configurado ({$r->stock_minimo}). Se requiere reposición.",
                    'leida'       => false,
                    'enviada'     => false,
                    'enviada_en'  => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        Log::info('NotifyLowStock', [
            'repuestos_bajos' => $repuestosBajos->count(),
            'codigos'         => $repuestosBajos->pluck('codigo')->toArray(),
        ]);

        $this->info("Proceso completado. {$repuestosBajos->count()} alerta(s) registradas.");
        return Command::SUCCESS;
    }
}
