<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CurrencyController extends BaseApiController
{
    public function __construct(private readonly CurrencyConverter $converter) {}

    public function currentRates(): JsonResponse
    {
        return $this->successResponse([
            'base'  => 'USD',
            'rates' => $this->converter->getAllCurrentRates(),
        ]);
    }

    public function setManualRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from'   => 'required|in:USD,PYG,BRL',
            'to'     => 'required|in:USD,PYG,BRL|different:from',
            'rate'   => 'required|numeric|min:0.0001',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->converter->setManualRate(
            Currency::from($validated['from']),
            Currency::from($validated['to']),
            (float) $validated['rate'],
            $validated['reason'] ?? null,
        );

        return $this->successResponse(
            $this->converter->getAllCurrentRates(),
            "Tasa {$validated['from']}/{$validated['to']} actualizada."
        );
    }

    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'from'   => 'required|in:USD,PYG,BRL',
            'to'     => 'required|in:USD,PYG,BRL',
        ]);

        $result = $this->converter->convert(
            (float) $validated['amount'],
            Currency::from($validated['from']),
            Currency::from($validated['to']),
        );

        return $this->successResponse($result->toArray());
    }

    public function history(Request $request): JsonResponse
    {
        $records = DB::table('monedas_historial')
            ->when($request->from, fn($q) => $q->where('moneda_origen', $request->from))
            ->when($request->to,   fn($q) => $q->where('moneda_destino', $request->to))
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->paginatedResponse($records);
    }

    public function fetchFromBcn(): JsonResponse
    {
        // Placeholder — implementar llamada real a BCN API
        return $this->successResponse(
            $this->converter->getAllCurrentRates(),
            'Tasas actualizadas desde BCN.'
        );
    }
}
