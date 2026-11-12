<?php

declare(strict_types=1);

namespace App\Infrastructure\Currency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Trae las cotizaciones del día desde la portada pública de La Moneda (lamoneda.com.py)
 * y las guarda en `tasas_cambio`, la tabla que usan todos los formularios.
 *
 * Se lee la portada (permitida por su robots.txt); su /api/ está excluido para bots.
 */
final class LaMonedaRateSync
{
    /** created_by de las filas automáticas; un usuario real (> 0) indica carga manual. */
    public const SISTEMA = 0;

    private const TABLE = 'tasas_cambio';

    /** Moneda del ERP => fila de La Moneda (data-moneda, data-moneda2). */
    private const FILAS = ['PYG' => ['DOLAR', 'GUARANI'], 'BRL' => ['DOLAR', 'REAL']];

    /** Rangos de cordura (unidades por USD): no guardar basura si cambia la página ni aceptar tasas absurdas. */
    public const RANGOS = ['PYG' => [3000, 15000], 'BRL' => [2, 15]];

    /** Zona horaria del negocio: la app corre en UTC y de noche "hoy" sería mañana. */
    public const TZ = 'America/Asuncion';

    public static function hoy(): string
    {
        return now(self::TZ)->toDateString();
    }

    /** ¿Ya hay tasa de hoy para todas las monedas? */
    public function tieneTasasDeHoy(): bool
    {
        return DB::table(self::TABLE)->where('fecha', self::hoy())->distinct()->count('moneda_destino') >= count(self::FILAS);
    }

    /**
     * Sincroniza como mucho una vez cada N minutos (bloqueo en caché) y nunca lanza excepción:
     * pensado para llamarse desde una petición web.
     */
    public function syncIfDue(): void
    {
        $minutos = (int) config('erp.currency.lamoneda.intervalo_minutos', 30);
        if (!Cache::add('lamoneda:sync', true, now()->addMinutes($minutos))) {
            return;
        }

        try {
            $this->sync();
        } catch (\Throwable $e) {
            Log::warning('Cotización La Moneda no disponible: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, array{compra: float, venta: float}> tasas guardadas (PYG y BRL por USD)
     */
    public function sync(): array
    {
        $html = Http::connectTimeout(3)->timeout(5)
            ->withHeaders(['User-Agent' => 'DJTrucks-ERP/1.0 (cotizacion del dia)'])
            ->get(config('erp.currency.lamoneda.url'))
            ->throw()
            ->body();

        $tasas = $this->parse($html, (string) config('erp.currency.lamoneda.sucursal'));
        $guardadas = [];

        foreach ($tasas as $moneda => $tasa) {
            $deHoy = DB::table(self::TABLE)->where('moneda_destino', $moneda)->where('fecha', self::hoy());

            // Una carga manual del día manda sobre la automática.
            if ((clone $deHoy)->where('created_by', '!=', self::SISTEMA)->exists()) {
                continue;
            }
            $ultima = $deHoy->orderByDesc('id')->first();
            if ($ultima && (float) $ultima->compra === $tasa['compra'] && (float) $ultima->venta === $tasa['venta']) {
                $guardadas[$moneda] = $tasa;
                continue;
            }

            DB::table(self::TABLE)->insert([
                'fecha'          => self::hoy(),
                'moneda_destino' => $moneda,
                'compra'         => $tasa['compra'],
                'venta'          => $tasa['venta'],
                'created_by'     => self::SISTEMA,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $guardadas[$moneda] = $tasa;
        }

        // CurrencyConverter (API) lee estas claves; sin ellas usa las tasas fijas de config.
        foreach ($guardadas as $moneda => $tasa) {
            Cache::put("rate_USD_{$moneda}", $tasa['venta'], now()->addDay());
            Cache::put("rate_{$moneda}_USD", 1 / $tasa['venta'], now()->addDay());
        }

        return $guardadas;
    }

    /**
     * Extrae compra/venta por USD (PYG y BRL) del panel visible de la sucursal indicada.
     * Filas usadas: DOLAR/GUARANI (guaraníes por dólar) y DOLAR/REAL (arbitraje, reales por dólar).
     *
     * @return array<string, array{compra: float, venta: float}>
     */
    public function parse(string $html, string $sucursal): array
    {
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        $panel = $xp->query(sprintf('//*[@id=%s]', $this->xpathLiteral('trading-panel-' . $sucursal)))->item(0);
        if (!$panel) {
            throw new RuntimeException("Sucursal '{$sucursal}' no encontrada en la página de La Moneda.");
        }

        $tasas = [];
        foreach (self::FILAS as $moneda => [$m1, $m2]) {
            $fila = $xp->query(sprintf('.//tr[@data-moneda=%s and @data-moneda2=%s]', $this->xpathLiteral($m1), $this->xpathLiteral($m2)), $panel)->item(0);
            $celdas = $fila ? $xp->query('./td[contains(@class,"lm-rate-cell")]', $fila) : null;
            if (!$celdas || $celdas->length < 2) {
                throw new RuntimeException("Cotización {$m1}/{$m2} ausente en La Moneda.");
            }
            $t = ['compra' => $this->numero($celdas->item(0)->textContent), 'venta' => $this->numero($celdas->item(1)->textContent)];

            [$min, $max] = self::RANGOS[$moneda];
            if ($t['venta'] < $min || $t['venta'] > $max || $t['compra'] <= 0 || $t['compra'] > $t['venta']) {
                throw new RuntimeException("Cotización {$m1}/{$m2} fuera de rango en La Moneda.");
            }
            $tasas[$moneda] = $t;
        }

        return $tasas;
    }

    /** "5.950" → 5950; "3,65" → 3.65; "6.150,50" → 6150.5 (formato paraguayo). */
    private function numero(string $texto): float
    {
        $t = preg_replace('/[^\d.,]/', '', $texto);
        $t = str_contains($t, ',') ? str_replace(',', '.', str_replace('.', '', $t)) : str_replace('.', '', $t);
        return (float) $t;
    }

    private function xpathLiteral(string $s): string
    {
        return str_contains($s, "'") ? '"' . $s . '"' : "'" . $s . "'";
    }
}
