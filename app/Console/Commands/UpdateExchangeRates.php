<?php

namespace App\Console\Commands;

use App\Infrastructure\Currency\LaMonedaRateSync;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    protected $signature   = 'erp:update-exchange-rates';
    protected $description = 'Actualiza las tasas USD/PYG y USD/BRL desde La Moneda (lamoneda.com.py)';

    public function handle(LaMonedaRateSync $sync): int
    {
        try {
            foreach ($sync->sync() as $moneda => $tasa) {
                $this->line("  USD/{$moneda}: compra {$tasa['compra']} · venta {$tasa['venta']}");
            }
        } catch (\Throwable $e) {
            $this->error('No se pudo actualizar desde La Moneda: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Tasas de cambio actualizadas.');
        return Command::SUCCESS;
    }
}
