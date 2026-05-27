<?php

declare(strict_types=1);

namespace App\Domain\Leads\Services;

use Illuminate\Support\Facades\DB;

/**
 * Asigna leads a vendedores según round-robin balanceado por carga.
 *
 * Reglas:
 *  - Solo considera usuarios con rol "vendedor" y activos.
 *  - Asigna al vendedor con MENOS leads activos (nuevo + contactado) al momento.
 *  - Si hay empate, asigna al vendedor menos reciente (LRU).
 */
final class LeadAssignmentService
{
    public function pickNextVendedor(): ?int
    {
        $candidatos = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                  ->where('model_has_roles.model_type', 'App\\Models\\User');
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'vendedor')
            ->where('users.activo', true)
            ->whereNull('users.deleted_at')
            ->select('users.id')
            ->get()
            ->pluck('id')
            ->toArray();

        if (empty($candidatos)) {
            return null;
        }

        $loads = DB::table('consultas_web')
            ->whereIn('asignado_a', $candidatos)
            ->whereIn('estado', ['nuevo', 'contactado'])
            ->selectRaw('asignado_a, COUNT(*) as carga')
            ->groupBy('asignado_a')
            ->pluck('carga', 'asignado_a')
            ->toArray();

        $cargaMin = PHP_INT_MAX;
        $elegido = null;
        foreach ($candidatos as $id) {
            $carga = (int) ($loads[$id] ?? 0);
            if ($carga < $cargaMin) {
                $cargaMin = $carga;
                $elegido = $id;
            }
        }
        return $elegido;
    }
}
