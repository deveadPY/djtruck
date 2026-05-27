<?php

declare(strict_types=1);

namespace App\Application\Warranties;

use App\Domain\Warranties\Exceptions\WarrantyException;
use App\Domain\Warranties\ValueObjects\WarrantyStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateClaimUseCase
{
    public function execute(CreateClaimDTO $dto): int
    {
        $garantia = DB::table('garantias')->where('id', $dto->garantiaId)->whereNull('deleted_at')->first();
        if (!$garantia) {
            throw new RuntimeException("Garantía {$dto->garantiaId} no encontrada.");
        }

        if ($garantia->estado !== WarrantyStatus::VIGENTE->value) {
            throw WarrantyException::notVigente($dto->garantiaId, $garantia->estado);
        }
        if ($garantia->vencimiento < now()->toDateString()) {
            throw WarrantyException::expired($garantia->vencimiento);
        }

        if (trim($dto->descripcionProblema) === '') {
            throw new RuntimeException('La descripción del problema es obligatoria.');
        }

        return DB::transaction(function () use ($dto) {
            $numero = $this->nextClaimNumber();
            return DB::table('reclamos_garantia')->insertGetId([
                'garantia_id'           => $dto->garantiaId,
                'numero_reclamo'        => $numero,
                'fecha_reclamo'         => now()->toDateString(),
                'descripcion_problema'  => trim($dto->descripcionProblema),
                'estado'                => 'ABIERTO',
                'cubierto_por_garantia' => true,
                'tecnico_asignado_id'   => $dto->tecnicoAsignadoId,
                'created_by'            => Auth::id(),
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        });
    }

    private function nextClaimNumber(): string
    {
        $year = now()->format('Y');
        $lastNum = (int) DB::table('reclamos_garantia')
            ->where('numero_reclamo', 'like', "RG-{$year}-%")
            ->orderByDesc('id')
            ->limit(1)
            ->value(DB::raw("CAST(SUBSTRING_INDEX(numero_reclamo, '-', -1) AS UNSIGNED)"));
        $next = str_pad((string) ($lastNum + 1), 5, '0', STR_PAD_LEFT);
        return "RG-{$year}-{$next}";
    }
}
