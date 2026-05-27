<?php

declare(strict_types=1);

namespace App\Domain\Leads\Repositories;

use App\Domain\Leads\Aggregates\Lead;

interface LeadRepositoryInterface
{
    public function save(Lead $lead): Lead;
    public function update(int $id, Lead $lead): Lead;
    public function findById(int $id): ?Lead;
}
