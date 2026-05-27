<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Application\Leads\ConvertLeadToSaleUseCase;
use App\Application\Sales\CreateSaleDTO;
use App\Application\Sales\CreateSaleUseCase;
use App\Domain\Quotes\Events\QuoteConvertedToSale;
use App\Domain\Quotes\Exceptions\QuoteException;
use App\Domain\Quotes\Repositories\QuoteRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * Convierte un presupuesto ACEPTADO (y no vencido) en una venta real.
 *
 * Flujo:
 *  1. Valida que el presupuesto puede convertirse.
 *  2. Marca el presupuesto como ACEPTADO (si está ENVIADO).
 *  3. Construye CreateSaleDTO con los items del presupuesto.
 *  4. Llama a CreateSaleUseCase (que crea venta + valida stock + cuotas).
 *  5. Marca presupuesto como CONVERTIDO con venta_id.
 *  6. Si tiene lead_id, lo cierra como ganado.
 *  7. Emite evento QuoteConvertedToSale.
 */
final class ConvertQuoteToSaleUseCase
{
    public function __construct(
        private readonly QuoteRepositoryInterface $repository,
        private readonly CreateSaleUseCase $createSaleUseCase,
        private readonly ConvertLeadToSaleUseCase $convertLeadUseCase,
    ) {}

    public function execute(int $quoteId): int
    {
        $quote = $this->repository->findById($quoteId);
        if (!$quote) {
            throw new RuntimeException("Presupuesto {$quoteId} no encontrado.");
        }

        if ($quote->getEstado()->value === 'CONVERTIDO') {
            throw QuoteException::alreadyConverted($quote->getNumero());
        }

        if ($quote->estaVencido()) {
            throw QuoteException::expired(
                $quote->getNumero(),
                $quote->toArray()['vigencia_hasta']
            );
        }

        // Si está BORRADOR o ENVIADO, marcarlo como ACEPTADO primero
        if (!$quote->puedeConvertirseAVenta()) {
            $quote->marcarAceptado();
            $this->repository->update($quoteId, $quote);
            $quote = $this->repository->findById($quoteId);
        }

        $ventaId = DB::transaction(function () use ($quote, $quoteId) {
            $items = array_map(function ($item) {
                return [
                    'itemable_id'         => $item['itemable_id'],
                    'itemable_type'       => $item['itemable_type'],
                    'descripcion'         => $item['descripcion'] ?? 'Item de presupuesto',
                    'cantidad'            => (float) $item['cantidad'],
                    'precio_unitario_usd' => (float) $item['precio_unitario_usd'],
                    'costo_snapshot_usd'  => $item['costo_snapshot_usd'] ?? 0,
                ];
            }, $quote->getItems());

            $arr = $quote->toArray();

            // Resolver vehiculo principal (primer item tipo Vehicle)
            $vehiculoPrincipal = null;
            foreach ($items as $it) {
                if (str_contains($it['itemable_type'], 'Vehicle')) {
                    $vehiculoPrincipal = (int) $it['itemable_id'];
                    break;
                }
            }

            $createDto = new CreateSaleDTO(
                clienteId:           $arr['cliente_id'],
                fechaVenta:          now()->toDateString(),
                monedaVenta:         $arr['moneda'],
                precioVentaMoneda:   $arr['total_usd'] * $arr['tasa_cambio'],
                precioVentaUsd:      $arr['total_usd'],
                tasaCambioVenta:     $arr['tasa_cambio'],
                modalidadPago:       $arr['modalidad_pago_sugerida'],
                items:               $items,
                pagos:               [],
                vehiculoPrincipalId: $vehiculoPrincipal,
                descuentoMoneda:     $arr['descuento_usd'] * $arr['tasa_cambio'],
                descuentoUsd:        $arr['descuento_usd'],
                tipoPlan:            $arr['modalidad_pago_sugerida'] === 'CUOTAS' ? 'MANUAL' : null,
                numeroCuotas:        $arr['cuotas_sugeridas'],
                tasaInteresMensual:  0,
                estado:              'EN_PROCESO',
                observaciones:       'Generada desde presupuesto ' . $quote->getNumero(),
            );

            $venta = $this->createSaleUseCase->execute($createDto);

            // Actualizar presupuesto
            DB::table('presupuestos')->where('id', $quoteId)->update([
                'estado'         => 'CONVERTIDO',
                'venta_id'       => $venta->id,
                'convertido_at'  => now(),
                'updated_by'     => Auth::id(),
                'updated_at'     => now(),
            ]);

            // Si el quote vino de un lead, cerrarlo como ganado
            if ($quote->getLeadId()) {
                $this->convertLeadUseCase->execute($quote->getLeadId(), $venta->id);
            }

            return (int) $venta->id;
        });

        Event::dispatch(new QuoteConvertedToSale($quoteId, $ventaId));

        return $ventaId;
    }
}
