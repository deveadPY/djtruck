<?php

namespace App\Console\Commands;

use App\Domain\Sales\Services\MoratoriumService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckOverdueInstallments extends Command
{
    protected $signature   = 'erp:check-overdue-installments';
    protected $description = 'Marca cuotas vencidas, acumula interés moratorio y dispara notificaciones';

    public function __construct(
        private readonly MoratoriumService $moratorium,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $hoy = now()->toDateString();
        $this->info("Revisando cuotas vencidas al {$hoy}...");

        // ── 1. Marcar PENDIENTES vencidas como VENCIDAS ──────────────────
        $marcadas = DB::table('cuotas')
            ->where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '<', $hoy)
            ->whereNull('deleted_at')
            ->update([
                'estado'     => 'VENCIDA',
                'updated_at' => now(),
            ]);

        $this->line("  ⚠️  {$marcadas} cuotas marcadas como VENCIDAS");

        // ── 2. Acumular interés moratorio diario ─────────────────────────
        $resultadoMora = $this->moratorium->acumularMoraDiaria();

        $this->line("  💰 {$resultadoMora['procesadas']} cuotas procesadas con mora");
        $this->line("  ➕ Mora acumulada hoy: USD " . number_format($resultadoMora['total_mora_acumulada'], 4));

        // ── 3. Disparar eventos de notificación para cuotas vencidas ─────
        $cuotasVencidas = DB::table('cuotas')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->whereIn('cuotas.estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('cuotas.deleted_at')
            ->select('cuotas.id', 'cuotas.venta_id', 'cuotas.fecha_vencimiento', 'ventas.cliente_id')
            ->get();

        foreach ($cuotasVencidas as $cuota) {
            $dias = max(0, (int) now()->diffInDays(\Carbon\Carbon::parse($cuota->fecha_vencimiento), false) * -1);
            event(new \App\Domain\Sales\Events\InstallmentOverdue(
                $cuota->id,
                $cuota->venta_id,
                $cuota->cliente_id,
                $dias,
            ));
        }

        $this->info("Proceso completado. Cuotas vencidas: {$cuotasVencidas->count()}");
        Log::info('CheckOverdueInstallments', [
            'marcadas_vencidas' => $marcadas,
            'mora_procesadas'   => $resultadoMora['procesadas'],
            'mora_acumulada'    => $resultadoMora['total_mora_acumulada'],
            'total_vencidas'    => $cuotasVencidas->count(),
        ]);

        return Command::SUCCESS;
    }
}
