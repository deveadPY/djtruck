<?php

declare(strict_types=1);

namespace App\Application\Warranties;

use App\Domain\Warranties\Aggregates\Warranty;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreateWarrantyUseCase
{
    public function execute(CreateWarrantyDTO $dto): int
    {
        $warranty = Warranty::create(
            ventaId:     $dto->ventaId,
            inicio:      new \DateTimeImmutable($dto->inicio),
            vencimiento: new \DateTimeImmutable($dto->vencimiento),
            tipo:        $dto->tipo,
            vehiculoId:  $dto->vehiculoId,
            repuestoId:  $dto->repuestoId,
            kmInicio:    $dto->kmInicio,
            kmLimite:    $dto->kmLimite,
            cobertura:   $dto->cobertura,
            exclusiones: $dto->exclusiones,
        );

        $data = $warranty->toArray();
        unset($data['id']);
        $data['created_by'] = Auth::id();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::transaction(fn() => DB::table('garantias')->insertGetId($data));
    }
}
