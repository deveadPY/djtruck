<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

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
}
