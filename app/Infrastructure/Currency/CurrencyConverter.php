<?php

declare(strict_types=1);

namespace App\Infrastructure\Currency;

use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\ExchangeRate;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\Exceptions\CurrencyConversionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

final class CurrencyConverter
{
    private const BASE = Currency::USD;
    private array $memCache = [];

    public function convert(
        float    $amount,
        Currency $from,
        Currency $to,
        ?int     $transactionId   = null,
        string   $transactionType = 'general',
    ): Money {
        if ($from === $to) return new Money($amount, $to);

        $rate            = $this->getEffectiveRate($from, $to);
        $convertedAmount = $this->applyRate($amount, $from, $to, $rate);

        if ($transactionId !== null) {
            $this->recordRateSnapshot($transactionId, $transactionType, $from, $to, $rate, $amount, $convertedAmount);
        }

        return new Money($convertedAmount, $to, $rate);
    }

    public function toBaseCurrency(float $amount, Currency $from): Money
    {
        return $this->convert($amount, $from, self::BASE);
    }

    public function fromBaseCurrency(float $amount, Currency $to): Money
    {
        return $this->convert($amount, self::BASE, $to);
    }

    public function getEffectiveRate(Currency $from, Currency $to): ExchangeRate
    {
        // Tasa manual
        $manual = $this->getManualRate($from, $to);
        if ($manual) return $manual;

        if ($to === Currency::USD) return $this->getRateToUsd($from);
        if ($from === Currency::USD) return $this->getRateFromUsd($to);

        // Cross-rate via USD
        $fromToUsd = $this->getRateToUsd($from);
        $usdToTo   = $this->getRateFromUsd($to);
        return new ExchangeRate(
            $fromToUsd->value * $usdToTo->value, $from, $to, 'cross_rate', Carbon::now()
        );
    }

    public function setManualRate(Currency $from, Currency $to, float $rate, ?string $reason = null): void
    {
        if ($rate <= 0) throw new CurrencyConversionException("Tasa debe ser positiva: {$rate}");
        $key = "manual_rate_{$from->value}_{$to->value}";
        Cache::put($key, ['rate' => $rate, 'reason' => $reason, 'set_at' => now()->toIso8601String()], now()->addHours(24));
        unset($this->memCache[$key]);
    }

    public function getAllCurrentRates(): array
    {
        $rates = [];
        foreach (Currency::nonBase() as $currency) {
            try {
                $rate = $this->getRateFromUsd($currency);
                $rates[$currency->value] = [
                    'rate'      => $rate->value,
                    'source'    => $rate->source,
                    'timestamp' => $rate->timestamp->toIso8601String(),
                    'label'     => "1 USD = {$rate->value} {$currency->value}",
                ];
            } catch (CurrencyConversionException $e) {
                $rates[$currency->value] = ['error' => $e->getMessage()];
            }
        }
        return $rates;
    }

    public function format(float $amount, Currency $currency): string
    {
        return match ($currency) {
            Currency::USD => '$ '   . number_format($amount, 2, '.', ','),
            Currency::PYG => '₲ '   . number_format($amount, 0, ',', '.'),
            Currency::BRL => 'R$ '  . number_format($amount, 2, ',', '.'),
        };
    }

    public function round(float $amount, Currency $currency): float
    {
        return round($amount, $currency->decimals());
    }

    // ────────────────────────────────────────────────────────
    private function getManualRate(Currency $from, Currency $to): ?ExchangeRate
    {
        $key = "manual_rate_{$from->value}_{$to->value}";
        $cached = Cache::get($key);
        if ($cached) {
            return new ExchangeRate($cached['rate'], $from, $to, 'manual', Carbon::now());
        }
        return null;
    }

    private function getRateToUsd(Currency $currency): ExchangeRate
    {
        if ($currency === Currency::USD) return ExchangeRate::parity(Currency::USD);

        $cacheKey = "rate_{$currency->value}_USD";
        if (isset($this->memCache[$cacheKey])) {
            return $this->buildRate($this->memCache[$cacheKey], $currency, Currency::USD, 'memory');
        }

        // Cache Redis/File
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $this->memCache[$cacheKey] = $cached;
            return $this->buildRate($cached, $currency, Currency::USD, 'cache');
        }

        // Config fallback (para XAMPP sin acceso a BCN API)
        $fallbackRates = [
            'PYG' => 1 / config('erp.currency.fallback_rates.USD_PYG', 7800),
            'BRL' => 1 / config('erp.currency.fallback_rates.USD_BRL', 5.05),
        ];

        if (isset($fallbackRates[$currency->value])) {
            $rate = $fallbackRates[$currency->value];
            Cache::put($cacheKey, $rate, now()->addHour());
            $this->memCache[$cacheKey] = $rate;
            Log::warning("CurrencyConverter: usando tasa fallback de config para {$currency->value}/USD");
            return $this->buildRate($rate, $currency, Currency::USD, 'config_fallback');
        }

        return $this->getLastSavedRate($currency, Currency::USD);
    }

    private function getRateFromUsd(Currency $currency): ExchangeRate
    {
        if ($currency === Currency::USD) return ExchangeRate::parity(Currency::USD);
        $rateToUsd = $this->getRateToUsd($currency);
        if ($rateToUsd->value <= 0) throw new CurrencyConversionException("Tasa cero para {$currency->value}");
        return new ExchangeRate(1 / $rateToUsd->value, Currency::USD, $currency, $rateToUsd->source . '_inv', $rateToUsd->timestamp);
    }

    private function applyRate(float $amount, Currency $from, Currency $to, ExchangeRate $rate): float
    {
        return $this->round($amount * $rate->value, $to);
    }

    private function getLastSavedRate(Currency $from, Currency $to): ExchangeRate
    {
        $record = DB::table('monedas_historial')
            ->where('moneda_origen', $from->value)
            ->where('moneda_destino', $to->value)
            ->orderByDesc('created_at')
            ->first();

        if (!$record) {
            throw new CurrencyConversionException(
                "Sin tasa disponible para {$from->value}/{$to->value}. " .
                "Configure una tasa manual en el panel o verifique la conexión."
            );
        }
        return $this->buildRate((float) $record->tasa_usada, $from, $to, 'db_fallback');
    }

    private function recordRateSnapshot(
        int $transactionId, string $transactionType,
        Currency $from, Currency $to, ExchangeRate $rate,
        float $original, float $converted,
    ): void {
        DB::table('monedas_historial')->insert([
            'transaction_id'   => $transactionId,
            'transaction_type' => $transactionType,
            'moneda_origen'    => $from->value,
            'moneda_destino'   => $to->value,
            'tasa_usada'       => $rate->value,
            'fuente_tasa'      => $rate->source,
            'monto_original'   => $original,
            'monto_convertido' => $converted,
            'tasa_fecha'       => $rate->timestamp->toDateTimeString(),
            'created_at'       => now(),
            'created_by'       => auth()->id() ?? 0,
        ]);
    }

    private function buildRate(float $value, Currency $from, Currency $to, string $source): ExchangeRate
    {
        return new ExchangeRate($value, $from, $to, $source, Carbon::now());
    }
}
