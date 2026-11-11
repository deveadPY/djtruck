<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Shared\ValueObjects\Currency;
use App\Infrastructure\Currency\CurrencyConverter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CajaService
 *
 * Servicio de dominio centralizado para operaciones de caja.
 * Garantiza que los 2 fondos del sistema (Caja Chica y Caja Capital)
 * sean identificados de forma estable y que los movimientos se
 * registren con consistencia.
 */
final class CajaService
{
    /** Cache en memoria para evitar queries repetidas en el mismo request */
    private array $idCache = [];

    public function __construct(
        private readonly CurrencyConverter $currency,
    ) {}

    // ─── Resolución de IDs ───────────────────────────────────────────────────

    public function cajaId(string $codigo): int
    {
        if (!isset($this->idCache[$codigo])) {
            $id = DB::table('cajas')
                ->where('codigo', $codigo)
                ->where('activo', true)
                ->value('id');

            if ($id === null) {
                throw new \RuntimeException("Caja [{$codigo}] no encontrada o inactiva. Verifique la configuración del sistema.");
            }

            $this->idCache[$codigo] = (int) $id;
        }

        return $this->idCache[$codigo];
    }

    public function cajaChicaId(): int
    {
        return $this->cajaId('CAJA_CHICA');
    }

    public function cajaCapitalId(): int
    {
        return $this->cajaId('CAJA_CAPITAL');
    }

    // ─── Registro de movimientos ─────────────────────────────────────────────

    /**
     * Registra un movimiento de caja en una transacción existente (no abre transacción propia).
     * Caller es responsable del DB::transaction().
     */
    public function registrar(
        int     $cajaId,
        string  $tipo,       // 'INGRESO' | 'EGRESO'
        string  $concepto,
        string  $moneda,
        float   $monto,
        float   $montoUsd,
        ?int    $referenciaId = null,
        ?string $refType      = null,
    ): int {
        return DB::table('movimientos_caja')->insertGetId([
            'caja_id'       => $cajaId,
            'tipo'          => $tipo,
            'concepto'      => $concepto,
            'moneda'        => $moneda,
            'monto'         => round($monto, 4),
            'monto_usd'     => round($montoUsd, 4),
            'referencia_id' => $referenciaId,
            'ref_type'      => $refType,
            'created_at'    => now(),
            'updated_at'    => now(),
            'created_by'    => Auth::id() ?? 0,
        ]);
    }

    // ─── Shortcuts para los casos de uso más frecuentes ─────────────────────

    public function egresoChica(string $concepto, string $moneda, float $monto, float $montoUsd, ?int $refId = null, ?string $refType = null): int
    {
        return $this->registrar($this->cajaChicaId(), 'EGRESO', $concepto, $moneda, $monto, $montoUsd, $refId, $refType);
    }

    public function egresoCapital(string $concepto, string $moneda, float $monto, float $montoUsd, ?int $refId = null, ?string $refType = null): int
    {
        return $this->registrar($this->cajaCapitalId(), 'EGRESO', $concepto, $moneda, $monto, $montoUsd, $refId, $refType);
    }

    public function ingresoCapital(string $concepto, string $moneda, float $monto, float $montoUsd, ?int $refId = null, ?string $refType = null): int
    {
        return $this->registrar($this->cajaCapitalId(), 'INGRESO', $concepto, $moneda, $monto, $montoUsd, $refId, $refType);
    }

    // ─── Consulta de saldo ───────────────────────────────────────────────────

    public function saldo(int $cajaId): array
    {
        $row = DB::table('movimientos_caja')
            ->where('caja_id', $cajaId)
            ->whereNull('deleted_at')
            ->selectRaw("
                SUM(CASE WHEN tipo = 'INGRESO' THEN monto_usd ELSE 0 END) AS total_ingresos_usd,
                SUM(CASE WHEN tipo = 'EGRESO'  THEN monto_usd ELSE 0 END) AS total_egresos_usd,
                SUM(CASE WHEN moneda = 'PYG' AND tipo = 'INGRESO' THEN monto ELSE 0 END) AS total_ingresos_pyg,
                SUM(CASE WHEN moneda = 'PYG' AND tipo = 'EGRESO'  THEN monto ELSE 0 END) AS total_egresos_pyg
            ")
            ->first();

        $ingresosUsd = (float) ($row->total_ingresos_usd ?? 0);
        $egresosUsd  = (float) ($row->total_egresos_usd  ?? 0);
        
        $ingresosPyg = (float) ($row->total_ingresos_pyg ?? 0);
        $egresosPyg  = (float) ($row->total_egresos_pyg  ?? 0);

        return [
            'total_ingresos_usd' => round($ingresosUsd, 2),
            'total_egresos_usd'  => round($egresosUsd,  2),
            'saldo_usd'          => round($ingresosUsd - $egresosUsd, 2),
            'saldo_pyg'          => round($ingresosPyg - $egresosPyg, 0),
        ];
    }

    public function saldoChica(): array   { return $this->saldo($this->cajaChicaId()); }
    public function saldoCapital(): array { return $this->saldo($this->cajaCapitalId()); }

    // ─── Moneda y saldo nativo ───────────────────────────────────────────────

    /** Moneda principal (USD, PYG, …) de una caja. */
    public function monedaPrincipal(int $cajaId): string
    {
        $moneda = DB::table('cajas')->where('id', $cajaId)->value('moneda_principal');
        return $moneda ? strtoupper((string) $moneda) : 'USD';
    }

    /**
     * Saldo expresado en la moneda principal de la caja.
     * Caja Capital → USD, Caja Chica → PYG.
     */
    public function saldoNativo(int $cajaId): array
    {
        $moneda = $this->monedaPrincipal($cajaId);
        $saldo  = $this->saldo($cajaId);

        return [
            'moneda' => $moneda,
            'saldo'  => $moneda === 'PYG' ? $saldo['saldo_pyg'] : $saldo['saldo_usd'],
        ];
    }

    // ─── Transferencia entre cajas (consciente de moneda) ────────────────────

    /**
     * Transfiere fondos entre dos cajas convirtiendo a la moneda de cada una.
     * El monto se expresa en la moneda de la caja ORIGEN.
     *
     * Ej.: transferir 100 desde CAJA_CAPITAL (USD) a CAJA_CHICA (PYG):
     *   - EGRESO de Capital:  100 USD
     *   - INGRESO a Chica:    100 × tasa  PYG
     * Ambos movimientos comparten el mismo monto_usd (valor económico transferido).
     */
    public function transferir(
        string  $origenCodigo,
        string  $destinoCodigo,
        float   $monto,
        ?string $concepto = null,
    ): array {
        if ($origenCodigo === $destinoCodigo) {
            throw new \RuntimeException('La caja de origen y destino no pueden ser la misma.');
        }
        if ($monto <= 0) {
            throw new \RuntimeException('El monto a transferir debe ser mayor a cero.');
        }

        $origenId  = $this->cajaId($origenCodigo);
        $destinoId = $this->cajaId($destinoCodigo);

        $monedaOrigenStr  = $this->monedaPrincipal($origenId);
        $monedaDestinoStr = $this->monedaPrincipal($destinoId);
        $monedaOrigen     = Currency::from($monedaOrigenStr);
        $monedaDestino    = Currency::from($monedaDestinoStr);

        // Monto convertido a la moneda de la caja DESTINO
        $montoDestino = $monedaOrigen === $monedaDestino
            ? $monto
            : $this->currency->convert($monto, $monedaOrigen, $monedaDestino)->amount;

        // Valor económico común en USD
        $montoUsd = $monedaOrigen === Currency::USD
            ? $monto
            : $this->currency->convert($monto, $monedaOrigen, Currency::USD)->amount;

        // Tasa aplicada (para trazabilidad en el concepto)
        $tasaLabel = '';
        if ($monedaOrigen !== $monedaDestino && $monto > 0) {
            $tasa = $montoDestino / $monto;
            $tasaLabel = sprintf(' (1 %s = %s %s)', $monedaOrigenStr, number_format($tasa, 2, ',', '.'), $monedaDestinoStr);
        }

        $conceptoBase = $concepto ?: "Transferencia {$origenCodigo} → {$destinoCodigo}";
        $conceptoFull = $conceptoBase . $tasaLabel;

        return DB::transaction(function () use ($origenId, $destinoId, $conceptoFull, $monedaOrigenStr, $monedaDestinoStr, $monto, $montoDestino, $montoUsd) {
            $egresoId = $this->registrar($origenId, 'EGRESO', $conceptoFull, $monedaOrigenStr, $monto, $montoUsd, null, 'transferencia');
            $ingresoId = $this->registrar($destinoId, 'INGRESO', $conceptoFull, $monedaDestinoStr, $montoDestino, $montoUsd, $egresoId, 'transferencia');

            return [
                'egreso_id'      => $egresoId,
                'ingreso_id'     => $ingresoId,
                'monto_origen'   => round($monto, 4),
                'moneda_origen'  => $monedaOrigenStr,
                'monto_destino'  => round($montoDestino, 4),
                'moneda_destino' => $monedaDestinoStr,
                'monto_usd'      => round($montoUsd, 4),
            ];
        });
    }
}
