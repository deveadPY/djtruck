<?php

declare(strict_types=1);

namespace App\Application\Leads;

use App\Domain\Leads\Events\LeadConverted;
use App\Domain\Leads\Repositories\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * Vincula un lead con la venta que se concretó.
 * Útil para reportes de conversión por canal (WhatsApp vs Formulario).
 */
final class ConvertLeadToSaleUseCase
{
    public function __construct(
        private readonly LeadRepositoryInterface $repository,
    ) {}

    public function execute(int $leadId, int $ventaId): void
    {
        $lead = $this->repository->findById($leadId);
        if (!$lead) {
            throw new RuntimeException("Lead {$leadId} no encontrado.");
        }

        $lead->cerrarConVenta($ventaId);
        $this->repository->update($leadId, $lead);

        Event::dispatch(new LeadConverted(
            leadId:     $leadId,
            ventaId:    $ventaId,
            vendedorId: $lead->getAsignadoA(),
        ));
    }
}
