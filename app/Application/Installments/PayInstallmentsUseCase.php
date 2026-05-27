<?php

declare(strict_types=1);

namespace App\Application\Installments;

use App\Domain\Finance\Services\CajaService;
use App\Domain\Sales\Services\PaymentImputationService;
use App\Domain\Sales\Services\EarlyPaymentDiscountService;
use App\Infrastructure\Jobs\SendCuotaPagadaEmailJob;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PayInstallmentsUseCase
{
    public function __construct(
        private readonly PaymentImputationService   $imputation,
        private readonly EarlyPaymentDiscountService $discount,
        private readonly CajaService                $caja,
    ) {}

    /**
     * Ejecuta el pago de una o más cuotas.
     *
     * @return array{recibo_id: int, numero_recibo: string, imputacion: array, descuento_aplicado: float, cuotas_pagadas: int}
     */
    public function execute(PayInstallmentsDTO $dto): array
    {
        // ── 1. Validaciones básicas ──────────────────────────────────────
        if (empty($dto->cuotasIds)) {
            throw new RuntimeException('Debe especificar al menos una cuota a pagar.');
        }
        if ($dto->montoPagado <= 0) {
            throw new RuntimeException('El monto a pagar debe ser mayor a cero.');
        }

        // ── 2. Cargar cuotas y validar ───────────────────────────────────
        $cuotas = DB::table('cuotas')
            ->whereIn('id', $dto->cuotasIds)
            ->whereNull('deleted_at')
            ->get();

        if ($cuotas->count() !== count($dto->cuotasIds)) {
            throw new RuntimeException('Una o más cuotas no existen o fueron eliminadas.');
        }

        // Verificar que ninguna esté ya pagada
        foreach ($cuotas as $c) {
            if ($c->estado === 'PAGADA') {
                throw new RuntimeException("La cuota #{$c->numero_cuota} ya está pagada.");
            }
        }

        // Todas deben pertenecer al mismo plan
        $planId = $cuotas->first()->plan_cuotas_id;
        foreach ($cuotas as $c) {
            if ($c->plan_cuotas_id !== $planId) {
                throw new RuntimeException('Todas las cuotas deben pertenecer al mismo plan de pagos.');
            }
        }

        $ventaId = $cuotas->first()->venta_id;
        $moneda  = $cuotas->first()->moneda;

        // ── 3. Calcular imputación ───────────────────────────────────────
        $cuotasData = $cuotas->map(fn($c) => [
            'capital'      => (float) $c->capital,
            'interes'      => (float) $c->interes,
            'interes_mora' => (float) $c->interes_mora,
            'monto_pagado' => (float) ($c->monto_pagado ?? 0),
        ])->toArray();

        $totalPendiente = $this->imputation->totalPendiente($cuotasData);

        $imputacion = $this->imputation->allocate($dto->montoPagado, $cuotasData);

        // ── 4. Calcular descuento por anticipo (opcional) ────────────────
        $descuentoAplicado = 0.0;
        if ($dto->aplicarDescuentoAnticipo && $dto->descuentoAnticipoPct > 0) {
            $cuotasParaDescuento = $cuotas->map(fn($c) => [
                'capital'           => (float) $c->capital,
                'fecha_vencimiento' => $c->fecha_vencimiento,
            ])->toArray();

            $descuentoAplicado = $this->discount->calcularMultiple(
                $cuotasParaDescuento,
                $dto->fechaPago,
                $dto->descuentoAnticipoPct,
                $dto->descuentoProporcional,
            );

            // El descuento no puede superar el capital imputado
            if ($descuentoAplicado > $imputacion->capital) {
                $descuentoAplicado = $imputacion->capital;
            }
        }

        // Imputación efectiva (con descuento)
        $capitalEfectivo = $imputacion->capital - $descuentoAplicado;

        // ── 5. Validar que el pago cubra al menos el mínimo ──────────────
        // Si no se pagó mora completa o interés completo, es pago parcial
        $moraPendienteTotal = array_sum(array_map(fn($c) => (float) ($c['interes_mora'] ?? 0), $cuotasData));
        $interesPendienteTotal = array_sum(array_map(fn($c) => (float) ($c['interes'] ?? 0), $cuotasData));
        $capitalPendienteTotal = array_sum(array_map(
            fn($c) => max(0, (float) $c['capital'] - (float) ($c['monto_pagado'] ?? 0)),
            $cuotasData
        ));

        $cubreTodo = $imputacion->mora >= $moraPendienteTotal
                  && $imputacion->interes >= $interesPendienteTotal
                  && $capitalEfectivo >= $capitalPendienteTotal;

        // ── 6. Persistir en transacción ──────────────────────────────────
        $cajaId = $dto->cajaId ?? $this->caja->cajaCapitalId();

        return DB::transaction(function () use (
            $cuotas, $dto, $imputacion, $descuentoAplicado, $capitalEfectivo,
            $cubreTodo, $planId, $ventaId, $moneda, $cajaId
        ) {
            $ahora = now();

            // ── 6a. Distribuir pago entre las cuotas ─────────────────────
            $restanteMora       = $imputacion->mora;
            $restanteInteres    = $imputacion->interes;
            $restanteCapital    = $capitalEfectivo;
            $restanteDescuento  = $descuentoAplicado;
            $cuotasPagadasIds   = [];

            foreach ($cuotas as $cuota) {
                $cap      = (float) $cuota->capital;
                $int      = (float) $cuota->interes;
                $mora     = (float) $cuota->interes_mora;
                $yaPagado = (float) ($cuota->monto_pagado ?? 0);

                $capPend  = max(0, $cap - $yaPagado);

                // Asignar mora a esta cuota
                $moraAsignada = min($restanteMora, $mora);
                $restanteMora -= $moraAsignada;

                // Asignar interés
                $interesAsignado = min($restanteInteres, $int);
                $restanteInteres -= $interesAsignado;

                // Asignar capital
                $capitalAsignado = min($restanteCapital, $capPend);
                $restanteCapital -= $capitalAsignado;

                // Asignar descuento (proporcional al capital de esta cuota)
                $descuentoAsignado = $capPend > 0 && $restanteDescuento > 0
                    ? round($restanteDescuento * ($capitalAsignado / max(0.0001, $capPend)), 4)
                    : 0;
                $restanteDescuento -= $descuentoAsignado;

                $totalPagadoCuota = $moraAsignada + $interesAsignado + $capitalAsignado;
                $nuevoMontoPagado = $yaPagado + $totalPagadoCuota;

                // Determinar nuevo estado
                $capitalCubierto = ($yaPagado + $capitalAsignado) >= ($cap - 0.001);
                $interesCubierto = $interesAsignado >= ($int - 0.001);
                $moraCubierta    = $moraAsignada >= ($mora - 0.001);

                if ($capitalCubierto && $interesCubierto && $moraCubierta) {
                    $nuevoEstado = 'PAGADA';
                } else {
                    $nuevoEstado = 'PAGADA_PARCIAL';
                }

                DB::table('cuotas')->where('id', $cuota->id)->update([
                    'estado'               => $nuevoEstado,
                    'fecha_pago_efectivo'  => $nuevoEstado === 'PAGADA' ? $dto->fechaPago : $cuota->fecha_pago_efectivo,
                    'monto_pagado'         => round($nuevoMontoPagado, 4),
                    'interes_mora'         => round($mora - $moraAsignada, 4),
                    'descuento_anticipo'   => round((float) ($cuota->descuento_anticipo ?? 0) + $descuentoAsignado, 4),
                    'caja_cobro_id'        => $cajaId,
                    'updated_by'           => $dto->userId,
                    'updated_at'           => $ahora,
                ]);

                if ($nuevoEstado === 'PAGADA') {
                    $cuotasPagadasIds[] = $cuota->id;
                }
            }

            // ── 6b. Generar número de recibo secuencial ──────────────────
            $numeroRecibo = $this->generarNumeroRecibo();

            // ── 6c. Crear recibo ─────────────────────────────────────────
            $tipo = count($dto->cuotasIds) > 1 ? 'MULTIPLE' : 'CUOTA';

            $reciboId = DB::table('recibos_cuota')->insertGetId([
                'numero_recibo'        => $numeroRecibo,
                'plan_cuotas_id'       => $planId,
                'venta_id'             => $ventaId,
                'tipo'                 => $tipo,
                'monto_capital'        => $imputacion->capital,
                'monto_interes'        => $imputacion->interes,
                'monto_mora'           => $imputacion->mora,
                'descuento_anticipo'   => $descuentoAplicado,
                'descuento_liquidacion' => 0,
                'total_pagado'         => round($imputacion->total - $descuentoAplicado, 4),
                'moneda'               => $moneda,
                'cuotas_ids'           => json_encode($dto->cuotasIds),
                'fecha_pago'           => $dto->fechaPago,
                'caja_id'              => $cajaId,
                'observaciones'        => $dto->observaciones,
                'created_by'           => $dto->userId,
                'created_at'           => $ahora,
                'updated_at'           => $ahora,
            ]);

            // ── 6d. Registrar movimiento en caja ─────────────────────────
            $totalEfectivo = round($imputacion->total - $descuentoAplicado, 4);
            $this->caja->registrar(
                $cajaId,
                'INGRESO',
                "Cobro cuotas — Recibo {$numeroRecibo} — Venta #{$ventaId}",
                $moneda,
                $totalEfectivo,
                $totalEfectivo,
                $reciboId,
                'recibo_cuota',
            );

            // ── 6e. Auditoría ────────────────────────────────────────────
            $this->auditar(
                'PAY_INSTALLMENT',
                'cuota',
                $dto->cuotasIds,
                $cuotas->map(fn($c) => [
                    'id'     => $c->id,
                    'estado' => $c->estado,
                ])->toArray(),
                [
                    'recibo_id'            => $reciboId,
                    'numero_recibo'        => $numeroRecibo,
                    'cuotas_pagadas'       => $cuotasPagadasIds,
                    'imputacion'           => $imputacion->toArray(),
                    'descuento_aplicado'   => $descuentoAplicado,
                    'aplicar_descuento'    => $dto->aplicarDescuentoAnticipo,
                    'descuento_pct'        => $dto->descuentoAnticipoPct,
                ],
                $dto->userId,
                $dto->ipAddress,
            );

            // ── 6f. Enviar recibos por email (async) ────────────────────
            foreach ($cuotasPagadasIds as $cuotaId) {
                SendCuotaPagadaEmailJob::dispatch($cuotaId, $dto->userId);
            }

            return [
                'recibo_id'         => $reciboId,
                'numero_recibo'     => $numeroRecibo,
                'imputacion'        => $imputacion->toArray(),
                'descuento_aplicado' => $descuentoAplicado,
                'cuotas_pagadas'    => count($cuotasPagadasIds),
                'cuotas_parciales'  => count($dto->cuotasIds) - count($cuotasPagadasIds),
            ];
        });
    }

    /**
     * Genera el siguiente número de recibo secuencial: RC-YYYYMM-NNNN
     */
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

    private function auditar(
        string $action,
        string $entityType,
        int|array $entityId,
        array $oldValues,
        array $newValues,
        int $userId,
        ?string $ipAddress,
    ): void {
        DB::table('audit_logs')->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => is_array($entityId) ? 0 : $entityId,
            'old_values'  => json_encode($oldValues),
            'new_values'  => json_encode($newValues),
            'metadata'    => is_array($entityId) ? json_encode(['cuotas_ids' => $entityId]) : null,
            'ip_address'  => $ipAddress,
            'created_at'  => now(),
        ]);
    }
}
