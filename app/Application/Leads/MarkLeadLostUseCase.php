<?php

declare(strict_types=1);

namespace App\Application\Leads;

use App\Domain\Leads\Repositories\LeadRepositoryInterface;
use RuntimeException;

final class MarkLeadLostUseCase
{
    public function __construct(
        private readonly LeadRepositoryInterface $repository,
    ) {}

    public function execute(int $leadId, string $motivo): void
    {
        $lead = $this->repository->findById($leadId);
        if (!$lead) {
            throw new RuntimeException("Lead {$leadId} no encontrado.");
        }

        $lead->marcarPerdido($motivo);
        $this->repository->update($leadId, $lead);
    }
}
