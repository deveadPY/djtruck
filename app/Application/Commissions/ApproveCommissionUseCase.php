<?php

declare(strict_types=1);

namespace App\Application\Commissions;

use App\Domain\Commissions\ValueObjects\CommissionStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ApproveCommissionUseCase
{
    public function execute(int $comisionId): void
    {
        $row = DB::table('comisiones_calculadas')->where('id', $comisionId)->first();
        if (!$row) {
            throw new RuntimeException("Comisión {$comisionId} no encontrada.");
        }

        $estadoActual = CommissionStatus::from($row->estado);
        if (!$estadoActual->puedeTransicionarA(CommissionStatus::APROBADA)) {
            throw new RuntimeException("La comisión está en {$row->estado} y no puede aprobarse.");
        }

        DB::table('comisiones_calculadas')->where('id', $comisionId)->update([
            'estado'           => 'APROBADA',
            'fecha_aprobacion' => now()->toDateString(),
            'aprobada_por'     => Auth::id(),
            'updated_at'       => now(),
        ]);
    }
}
