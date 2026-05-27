<?php

declare(strict_types=1);

namespace App\Application\Suppliers;

use App\Domain\Suppliers\Services\SupplierScoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RecordSupplierRatingUseCase
{
    private const CRITERIOS_VALIDOS = [
        'CALIDAD_PRODUCTO', 'TIEMPO_ENTREGA', 'PRECIO',
        'SERVICIO_POSTVENTA', 'COMUNICACION', 'GENERAL',
    ];

    public function __construct(
        private readonly SupplierScoreService $scoreService,
    ) {}

    public function execute(RecordSupplierRatingDTO $dto): int
    {
        if (!in_array($dto->criterio, self::CRITERIOS_VALIDOS, true)) {
            throw new RuntimeException("Criterio inválido: {$dto->criterio}");
        }
        if ($dto->puntaje < 1 || $dto->puntaje > 5) {
            throw new RuntimeException("Puntaje debe estar entre 1 y 5 (recibido: {$dto->puntaje})");
        }

        return DB::transaction(function () use ($dto) {
            $id = DB::table('calificaciones_proveedor')->insertGetId([
                'proveedor_id' => $dto->supplierId,
                'criterio'     => $dto->criterio,
                'puntaje'      => $dto->puntaje,
                'comentario'   => $dto->comentario,
                'compra_id'    => $dto->compraId,
                'created_by'   => Auth::id(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Recalcular score del proveedor
            $this->scoreService->recalcularYGuardar($dto->supplierId);

            return $id;
        });
    }
}
