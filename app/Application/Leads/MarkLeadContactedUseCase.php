<?php

declare(strict_types=1);

namespace App\Application\Leads;

use App\Domain\Leads\Repositories\LeadRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MarkLeadContactedUseCase
{
    public function __construct(
        private readonly LeadRepositoryInterface $repository,
    ) {}

    public function execute(int $leadId, ?string $resumen = null): void
    {
        $lead = $this->repository->findById($leadId);
        if (!$lead) {
            throw new RuntimeException("Lead {$leadId} no encontrado.");
        }

        $lead->marcarContactado();

        DB::transaction(function () use ($leadId, $lead, $resumen) {
            $this->repository->update($leadId, $lead);

            DB::table('lead_interacciones')->insert([
                'lead_id'           => $leadId,
                'tipo'              => 'LLAMADA',
                'asunto'            => 'Primer contacto',
                'descripcion'       => $resumen ?? 'Lead contactado por primera vez',
                'resultado'         => 'POSITIVO',
                'fecha_interaccion' => now(),
                'created_by'        => Auth::id() ?? 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        });
    }
}
