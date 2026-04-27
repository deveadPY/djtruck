<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckOverdueInstallments extends Command
{
    protected $signature   = 'erp:check-overdue-installments';
    protected $description = 'Marca cuotas vencidas y calcula intereses de mora';

    public function handle(): int
    {
        $hoy = now()->toDateString();
        $this->info("Revisando cuotas vencidas al {$hoy}...");

        $vencidas = DB::table('cuotas')
            ->where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '<', $hoy)
            ->whereNull('deleted_at')
            ->update([
                'estado'     => 'VENCIDA',
                'updated_at' => now(),
            ]);

        $this->line("  ⚠️  {$vencidas} cuotas marcadas como VENCIDAS");

        $cuotasVencidas = DB::table('cuotas')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->where('cuotas.estado', 'VENCIDA')
            ->whereNull('cuotas.deleted_at')
            ->select('cuotas.id', 'cuotas.venta_id', 'cuotas.fecha_vencimiento', 'ventas.cliente_id')
            ->get();

        foreach ($cuotasVencidas as $cuota) {
            $dias = (int) now()->diffInDays(\Carbon\Carbon::parse($cuota->fecha_vencimiento), false) * -1;
            $dias = max(0, $dias);
            event(new \App\Domain\Sales\Events\InstallmentOverdue(
                $cuota->id,
                $cuota->venta_id,
                $cuota->cliente_id,
                $dias,
            ));
        }

        $this->info("Proceso completado. Total en mora: {$cuotasVencidas->count()}");
        Log::info('CheckOverdueInstallments', ['vencidas' => $vencidas, 'total_mora' => $cuotasVencidas->count()]);

        return Command::SUCCESS;
    }
}
