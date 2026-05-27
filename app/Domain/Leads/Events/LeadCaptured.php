<?php

declare(strict_types=1);

namespace App\Domain\Leads\Events;

class LeadCaptured
{
    public function __construct(
        public readonly int     $leadId,
        public readonly ?int    $vehiculoId,
        public readonly string  $nombre,
        public readonly string  $canal,
    ) {}
}
