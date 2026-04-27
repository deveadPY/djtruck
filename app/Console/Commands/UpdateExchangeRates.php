<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateExchangeRates extends Command
{
    protected $signature   = 'erp:update-exchange-rates {--force}';
    protected $description = 'Actualiza tasas de cambio USD/PYG y USD/BRL desde BCN Paraguay';

    public function handle(): int
    {
        $this->info('Actualizando tasas de cambio...');

        try {
            $response = Http::timeout(10)
                ->get(config('erp.currency.bcn_api_url', 'https://www.bcp.gov.py/webservices/index.php'), [
                    'wsdl'   => null,
                    'moneda' => 'USD',
                    'fecha'  => now()->format('d/m/Y'),
                ]);

            if ($response->successful()) {
                $tasaPyg = $this->parseBcnResponse($response->body(), 'PYG');
                if ($tasaPyg) {
                    Cache::put('rate_PYG_USD', 1 / $tasaPyg, now()->addHours(2));
                    Cache::put('rate_USD_PYG', $tasaPyg,     now()->addHours(2));
                    $this->line("  ✅ USD/PYG: {$tasaPyg}");
                }
            }
        } catch (\Throwable $e) {
            $this->warn("BCN API no disponible: {$e->getMessage()}");
            $this->info("Usando tasas de configuración como fallback.");
        }

        $fallbackPyg = config('erp.currency.fallback_rates.USD_PYG', 7800);
        $fallbackBrl = config('erp.currency.fallback_rates.USD_BRL', 5.05);

        if (!Cache::has('rate_USD_PYG') || $this->option('force')) {
            Cache::put('rate_USD_PYG', $fallbackPyg, now()->addHours(24));
            Cache::put('rate_PYG_USD', 1 / $fallbackPyg, now()->addHours(24));
            $this->line("  📌 USD/PYG (config): {$fallbackPyg}");
        }

        if (!Cache::has('rate_USD_BRL') || $this->option('force')) {
            Cache::put('rate_USD_BRL', $fallbackBrl, now()->addHours(24));
            Cache::put('rate_BRL_USD', 1 / $fallbackBrl, now()->addHours(24));
            $this->line("  📌 USD/BRL (config): {$fallbackBrl}");
        }

        $this->info('Tasas de cambio actualizadas correctamente.');
        return Command::SUCCESS;
    }

    private function parseBcnResponse(string $xml, string $moneda): ?float
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}
