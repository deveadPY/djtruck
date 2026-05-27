<?php

declare(strict_types=1);

namespace App\Application\Parts;

use App\Domain\Parts\Repositories\PartRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Descontinúa (o reactiva) un repuesto.
 *
 * Un repuesto descontinuado:
 *   - No aparece en alertas de stock bajo
 *   - No aparece en selectores de venta
 *   - Conserva su historial (no se elimina)
 *
 * Operación reversible mediante el flag `discontinue`.
 */
class DiscontinuePartUseCase
{
    public function __construct(
        private readonly PartRepositoryInterface $repository
    ) {}

    public function execute(int $partId, bool $discontinue = true): void
    {
        $part = $this->repository->findById($partId);

        if (!$part) {
            throw new RuntimeException("Repuesto #{$partId} no encontrado.");
        }

        // Invariante de dominio: usar el aggregate para mantener consistencia
        if ($discontinue) {
            $part->desactivar();
        } else {
            $part->activar();
        }

        // Persistencia mínima (solo el campo afectado) para evitar overrides accidentales
        DB::table('stock_repuestos')
            ->where('id', $partId)
            ->whereNull('deleted_at')
            ->update([
                'activo'     => !$discontinue ? true : false,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);
    }
}
