<?php

declare(strict_types=1);

namespace App\Application\Installments;

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Services\LiquidationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LiquidatePlanUseCase
{
    public function __construct(
        private readonly LiquidationService $liquidation,
        private readonly CajaService        $caja,
    ) {}

    /**
     * Liquida (cancela por completo) un plan de cuotas.
     *
     * @return array{recibo_id: int, numero_recibo: string, capital_pendiente: float, interes_no_devengado: float, descuento_aplicado: float, total_liquidacion: float}
     */
    public function execute(LiquidatePlanDTO $dto): array
    {
        // ── 1. Cargar plan ───────────────────────────────────────────────
        $plan = DB::table('planes_cuotas')->where('id', $dto->planId)->first();
        if (!$plan) {
            throw new RuntimeException('El plan de cuotas no existe.');
        }
        if ($plan->estado === 'COMPLETADO') {
            throw new RuntimeException('Este plan ya fue liquidado.');
        }
        if ($plan->estado === 'CANCELADO') {
            throw new RuntimeException('Este plan está cancelado y no puede liquidarse.');
        }

        // ── 2. Cargar cuotas pendientes ──────────────────────────────────
        $cuotas = DB::table('cuotas')
            ->where('plan_cuotas_id', $dto->planId)
            ->whereIn('estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->whereNull('deleted_at')
            ->get();

        if ($cuotas->isEmpty()) {
            throw new RuntimeException('No hay cuotas pendientes para liquidar en este plan.');
        }

        // ── 3. Calcular liquidación ─────────────────────────────────────
        $pctDescuento = $dto->aplicarDescuentoLiquidacion
            ? ($dto->descuentoLiquidacionPct ?? 0)
            : 0;

        $cuotasArray = $cuotas->map(fn($c) => (array) $c)->toArray();

        $resultado = $this->liquidation->calcular(
            $cuotasArray,
            $pctDescuento,
            $dto->fechaLiquidacion,
        );

        $totalLiquidacion = $resultado['total_liquidacion'];

        if ($totalLiquidacion <= 0) {
            throw new RuntimeException('El saldo de liquidación es $0. El plan ya está saldado.');
        }

        // ── 4. Persistir en transacción ──────────────────────────────────
        $ventaId = $plan->venta_id;
        $moneda  = $plan->moneda;
        $cajaId  = $dto->cajaId ?? $this->caja->cajaCapitalId();

        return DB::transaction(function () use (
            $dto, $plan, $cuotas, $resultado, $totalLiquidacion,
            $ventaId, $moneda, $cajaId, $pctDescuento
        ) {
            $ahora = now();

            // ── 4a. Marcar TODAS las cuotas como PAGADA ──────────────────
            $cuotasPagadasIds = [];
            foreach ($cuotas as $cuota) {
                DB::table('cuotas')->where('id', $cuota->id)->update([
                    'estado'                => 'PAGADA',
                    'fecha_pago_efectivo'   => $dto->fechaLiquidacion,
                    'monto_pagado'          => round((float) $cuota->capital, 4),
                    'interes_mora'          => 0,
                    'descuento_liquidacion' => round(
                        $pctDescuento > 0
                            ? (float) ($cuota->interes ?? 0) * ($pctDescuento / 100)
                            : 0,
                        4
                    ),
                    'caja_cobro_id'         => $cajaId,
                    'updated_by'            => $dto->userId,
                    'updated_at'            => $ahora,
                ]);
                $cuotasPagadasIds[] = $cuota->id;
            }

            // ── 4b. Marcar plan como COMPLETADO ──────────────────────────
            DB::table('planes_cuotas')->where('id', $dto->planId)->update([
                'estado'                          => 'COMPLETADO',
                'fecha_liquidacion'               => $dto->fechaLiquidacion,
                'descuento_liquidacion_aplicado'   => $resultado['descuento_aplicado'],
                'updated_at'                      => $ahora,
            ]);

            // ── 4c. Generar recibo ───────────────────────────────────────
            $numeroRecibo = $this->generarNumeroRecibo();

            $reciboId = DB::table('recibos_cuota')->insertGetId([
                'numero_recibo'         => $numeroRecibo,
                'plan_cuotas_id'        => $dto->planId,
                'venta_id'              => $ventaId,
                'tipo'                  => 'LIQUIDACION',
                'monto_capital'         => $resultado['capital_pendiente'],
                'monto_interes'         => $resultado['interes_devengado'] + $resultado['interes_no_devengado'],
                'monto_mora'            => $resultado['mora_total'],
                'descuento_anticipo'    => 0,
                'descuento_liquidacion' => $resultado['descuento_aplicado'],
                'total_pagado'          => $totalLiquidacion,
                'moneda'                => $moneda,
                'cuotas_ids'            => json_encode($cuotasPagadasIds),
                'fecha_pago'            => $dto->fechaLiquidacion,
                'caja_id'               => $cajaId,
                'observaciones'         => $dto->observaciones,
                'created_by'            => $dto->userId,
                'created_at'            => $ahora,
                'updated_at'            => $ahora,
            ]);

            // ── 4d. Registrar movimiento en caja ─────────────────────────
            $this->caja->registrar(
                $cajaId,
                'INGRESO',
                "Liquidación plan #{$dto->planId} — Recibo {$numeroRecibo} — Venta #{$ventaId}",
                $moneda,
                $totalLiquidacion,
                $totalLiquidacion,
                $reciboId,
                'recibo_cuota',
            );

            // ── 4e. Auditoría ────────────────────────────────────────────
            DB::table('audit_logs')->insert([
                'user_id'     => $dto->userId,
                'action'      => 'LIQUIDATE_PLAN',
                'entity_type' => 'plan_cuotas',
                'entity_id'   => $dto->planId,
                'old_values'  => json_encode([
                    'estado'  => $plan->estado,
                    'cuotas_pendientes' => $cuotas->count(),
                ]),
                'new_values'  => json_encode([
                    'estado'              => 'COMPLETADO',
                    'recibo_id'           => $reciboId,
                    'numero_recibo'       => $numeroRecibo,
                    'total_liquidacion'   => $totalLiquidacion,
                    'descuento_aplicado'  => $resultado['descuento_aplicado'],
                    'pct_descuento'       => $pctDescuento,
                    'aplicar_descuento'   => $dto->aplicarDescuentoLiquidacion,
                    'cuotas_liquidadas'   => $cuotasPagadasIds,
                ]),
                'ip_address'  => $dto->ipAddress,
                'created_at'  => $ahora,
            ]);

            return [
                'recibo_id'             => $reciboId,
                'numero_recibo'         => $numeroRecibo,
                'capital_pendiente'     => $resultado['capital_pendiente'],
                'interes_devengado'     => $resultado['interes_devengado'],
                'interes_no_devengado'  => $resultado['interes_no_devengado'],
                'mora_total'            => $resultado['mora_total'],
                'descuento_aplicado'    => $resultado['descuento_aplicado'],
                'total_liquidacion'     => $totalLiquidacion,
                'cuotas_liquidadas'     => count($cuotasPagadasIds),
                'detalle'               => $resultado['detalle'],
            ];
        });
    }

    private function generarNumeroRecibo(): string
    {
        $periodo = now()->format('Ym');

        $ultimo = DB::table('recibos_cuota')
            ->where('numero_recibo', 'like', "RC-{$periodo}-%")
            ->orderBy('numero_recibo', 'desc')
            ->value('numero_recibo');

        $secuencia = 1;
        if ($ultimo && preg_match('/RC-\d{6}-(\d{4})$/', $ultimo, $m)) {
            $secuencia = (int) $m[1] + 1;
        }

        return sprintf('RC-%s-%04d', $periodo, $secuencia);
    }
}
