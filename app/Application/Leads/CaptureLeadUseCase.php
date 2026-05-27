<?php

declare(strict_types=1);

namespace App\Application\Leads;

use App\Domain\Leads\Aggregates\Lead;
use App\Domain\Leads\Events\LeadCaptured;
use App\Domain\Leads\Repositories\LeadRepositoryInterface;
use App\Domain\Leads\Services\LeadAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Captura un lead desde el catálogo web público.
 * Auto-asigna a un vendedor disponible (round-robin balanceado por carga).
 */
final class CaptureLeadUseCase
{
    public function __construct(
        private readonly LeadRepositoryInterface $repository,
        private readonly LeadAssignmentService $assignment,
    ) {}

    public function execute(CaptureLeadDTO $dto): Lead
    {
        $lead = Lead::capture(
            vehiculoId: $dto->vehiculoId,
            nombre:     $dto->nombre,
            telefono:   $dto->telefono,
            email:      $dto->email,
            canal:      $dto->canal,
            mensaje:    $dto->mensaje,
        );

        // Auto-asignar a vendedor
        $vendedorId = $this->assignment->pickNextVendedor();
        if ($vendedorId !== null) {
            $lead->asignarA($vendedorId);
        }

        $saved = DB::transaction(function () use ($lead, $dto) {
            $saved = $this->repository->save($lead);

            // Guardar metadata adicional no parte del agregado
            if ($dto->ipAddress || $dto->userAgent) {
                DB::table('consultas_web')
                    ->where('id', $saved->getId()->value())
                    ->update([
                        'ip_address' => $dto->ipAddress,
                        'user_agent' => $dto->userAgent,
                    ]);
            }
            return $saved;
        });

        Event::dispatch(new LeadCaptured(
            leadId:     $saved->getId()->value(),
            vehiculoId: $saved->getVehiculoId(),
            nombre:     $dto->nombre,
            canal:      $dto->canal,
        ));

        return $saved;
    }
}
