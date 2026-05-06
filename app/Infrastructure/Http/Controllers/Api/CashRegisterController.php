<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CashRegisterController extends BaseApiController
{
    public function __construct(private readonly CurrencyConverter $currency) {}

    public function index(): JsonResponse
    {
        $cajas = DB::table('cajas')->get();
        return $this->successResponse($cajas);
    }

    public function show(int $id): JsonResponse
    {
        $caja = DB::table('cajas')->findOrFail($id);
        return $this->successResponse($caja);
    }

    public function balance(int $id): JsonResponse
    {
        DB::table('cajas')->where('id', $id)->firstOrFail();

        $ingresos = DB::table('movimientos_caja')
            ->where('caja_id', $id)->where('tipo', 'INGRESO')
            ->whereNull('deleted_at')->sum('monto_usd');

        $egresos = DB::table('movimientos_caja')
            ->where('caja_id', $id)->where('tipo', 'EGRESO')
            ->whereNull('deleted_at')->sum('monto_usd');

        return $this->successResponse([
            'caja_id'       => $id,
            'saldo_usd'     => round($ingresos - $egresos, 2),
            'total_ingresos'=> round($ingresos, 2),
            'total_egresos' => round($egresos, 2),
            'saldo_pyg'     => $this->currency->format(
                $this->currency->fromBaseCurrency($ingresos - $egresos, Currency::PYG)->amount,
                Currency::PYG
            ),
        ]);
    }

    public function transactions(int $id): JsonResponse
    {
        $txs = DB::table('movimientos_caja')
            ->where('caja_id', $id)
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data'    => $txs->items(),
            'pagination' => [
                'total'        => $txs->total(),
                'current_page' => $txs->currentPage(),
                'last_page'    => $txs->lastPage(),
            ],
        ]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'caja_origen_id'   => 'required|integer',
            'caja_destino_id'  => 'required|integer|different:caja_origen_id',
            'moneda'           => 'required|in:USD,PYG,BRL',
            'monto'            => 'required|numeric|min:0.01',
            'concepto'         => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $moneda   = Currency::from($validated['moneda']);
            $montoUsd = $moneda === Currency::USD
                ? $validated['monto']
                : $this->currency->toBaseCurrency($validated['monto'], $moneda)->amount;

            $concepto = $validated['concepto'] ?? "Transferencia entre cajas";

            DB::table('movimientos_caja')->insert([
                [
                    'caja_id'    => $validated['caja_origen_id'],
                    'tipo'       => 'EGRESO',
                    'concepto'   => $concepto,
                    'moneda'     => $validated['moneda'],
                    'monto'      => $validated['monto'],
                    'monto_usd'  => $montoUsd,
                    'created_at' => now(),
                    'created_by' => auth()->id(),
                ],
                [
                    'caja_id'    => $validated['caja_destino_id'],
                    'tipo'       => 'INGRESO',
                    'concepto'   => $concepto,
                    'moneda'     => $validated['moneda'],
                    'monto'      => $validated['monto'],
                    'monto_usd'  => $montoUsd,
                    'created_at' => now(),
                    'created_by' => auth()->id(),
                ],
            ]);
        });

        return $this->successResponse(null, 'Transferencia realizada exitosamente.');
    }

    public function reconcile(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'monto_fisico'  => 'required|numeric|min:0',
            'moneda'        => 'required|in:USD,PYG,BRL',
            'observaciones' => 'nullable|string',
        ]);

        $saldo = DB::table('movimientos_caja')
            ->where('caja_id', $id)
            ->selectRaw("SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END) as saldo")
            ->where('moneda', $validated['moneda'])
            ->value('saldo') ?? 0;

        $diferencia = $validated['monto_fisico'] - $saldo;

        $arqueoId = DB::table('arqueos_caja')->insertGetId([
            'caja_id'       => $id,
            'moneda'        => $validated['moneda'],
            'saldo_sistema' => $saldo,
            'saldo_fisico'  => $validated['monto_fisico'],
            'diferencia'    => $diferencia,
            'observaciones' => $validated['observaciones'],
            'created_at'    => now(),
            'created_by'    => auth()->id(),
        ]);

        return $this->successResponse([
            'arqueo_id'     => $arqueoId,
            'saldo_sistema' => $saldo,
            'saldo_fisico'  => $validated['monto_fisico'],
            'diferencia'    => $diferencia,
            'estado'        => $diferencia == 0 ? 'CUADRADO' : ($diferencia > 0 ? 'SOBRANTE' : 'FALTANTE'),
        ], 'Arqueo realizado.');
    }

    public function reconciliations(int $id): JsonResponse
    {
        $arqueos = DB::table('arqueos_caja')
            ->where('caja_id', $id)
            ->orderByDesc('created_at')->paginate(20);

        return response()->json(['success' => true, 'data' => $arqueos->items()]);
    }
}
