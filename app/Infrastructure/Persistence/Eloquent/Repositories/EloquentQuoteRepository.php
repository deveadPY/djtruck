<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Quotes\Aggregates\Quote;
use App\Domain\Quotes\Repositories\QuoteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\PresupuestoItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\PresupuestoModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class EloquentQuoteRepository implements QuoteRepositoryInterface
{
    public function save(Quote $quote): Quote
    {
        $data = $quote->toArray();
        unset($data['id']);
        $data['created_by'] = Auth::id();

        $model = PresupuestoModel::create($data);

        foreach ($quote->getItems() as $item) {
            PresupuestoItemModel::create([
                'presupuesto_id'      => $model->id,
                'itemable_id'         => $item['itemable_id'],
                'itemable_type'       => $item['itemable_type'],
                'descripcion'         => $item['descripcion'] ?? '—',
                'cantidad'            => $item['cantidad'],
                'precio_unitario_usd' => $item['precio_unitario_usd'],
                'subtotal_usd'        => (float) $item['cantidad'] * (float) $item['precio_unitario_usd'],
            ]);
        }

        return $quote->withId($model->id);
    }

    public function update(int $id, Quote $quote): Quote
    {
        $data = $quote->toArray();
        unset($data['id']);
        $data['updated_by'] = Auth::id();

        PresupuestoModel::where('id', $id)->update($data);
        return $quote->withId($id);
    }

    public function findById(int $id): ?Quote
    {
        $model = PresupuestoModel::with('items')->find($id);
        if (!$model) {
            return null;
        }

        $items = $model->items->map(fn($it) => [
            'itemable_id'         => $it->itemable_id,
            'itemable_type'       => $it->itemable_type,
            'descripcion'         => $it->descripcion,
            'cantidad'            => (float) $it->cantidad,
            'precio_unitario_usd' => (float) $it->precio_unitario_usd,
            'subtotal_usd'        => (float) $it->subtotal_usd,
        ])->toArray();

        $quote = Quote::create(
            numero:                $model->numero_presupuesto,
            clienteId:             $model->cliente_id,
            fechaEmision:          new \DateTimeImmutable($model->fecha_emision->format('Y-m-d')),
            vigenciaHasta:         new \DateTimeImmutable($model->vigencia_hasta->format('Y-m-d')),
            items:                 $items,
            leadId:                $model->lead_id,
            vendedorId:            $model->vendedor_id,
            moneda:                $model->moneda,
            tasaCambio:            (float) $model->tasa_cambio,
            descuentoUsd:          (float) $model->descuento_usd,
            modalidadPagoSugerida: $model->modalidad_pago_sugerida,
            cuotasSugeridas:       $model->cuotas_sugeridas,
            observaciones:         $model->observaciones,
            terminosCondiciones:   $model->terminos_condiciones,
        );

        // Reconstruir estado
        $reflection = new \ReflectionClass($quote);
        $estadoProp = $reflection->getProperty('estado');
        $estadoProp->setValue($quote, \App\Domain\Quotes\ValueObjects\QuoteStatus::from($model->estado));

        return $quote->withId($model->id);
    }

    public function nextNumero(): string
    {
        $year = now()->format('Y');
        $lastNum = (int) DB::table('presupuestos')
            ->where('numero_presupuesto', 'like', "P-{$year}-%")
            ->orderByDesc('id')
            ->limit(1)
            ->value(DB::raw("CAST(SUBSTRING_INDEX(numero_presupuesto, '-', -1) AS UNSIGNED)"));

        $next = str_pad((string) ($lastNum + 1), 5, '0', STR_PAD_LEFT);
        return "P-{$year}-{$next}";
    }
}
