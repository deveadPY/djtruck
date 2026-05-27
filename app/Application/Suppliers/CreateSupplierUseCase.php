<?php

declare(strict_types=1);

namespace App\Application\Suppliers;

use App\Domain\Suppliers\Aggregates\Supplier;
use App\Domain\Suppliers\Repositories\SupplierRepositoryInterface;
use App\Domain\Suppliers\Validators\UniqueSupplierRucValidator;
use Illuminate\Support\Facades\DB;

final class CreateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
        private readonly UniqueSupplierRucValidator $rucValidator,
    ) {}

    public function execute(CreateSupplierDTO $dto): Supplier
    {
        $this->rucValidator->validate($dto->rucRutNit);

        $supplier = Supplier::create(
            rucRutNit:                  $dto->rucRutNit,
            razonSocial:                $dto->razonSocial,
            nombreFantasia:             $dto->nombreFantasia,
            pais:                       $dto->pais,
            tipo:                       $dto->tipo,
            monedaPrincipal:            $dto->monedaPrincipal,
            diasCredito:                $dto->diasCredito,
            descuentoPagoAnticipadoPct: $dto->descuentoPagoAnticipadoPct,
            email:                      $dto->email,
            telefono:                   $dto->telefono,
            direccion:                  $dto->direccion,
            ciudad:                     $dto->ciudad,
            sitioWeb:                   $dto->sitioWeb,
            contactoPrincipal:          $dto->contactoPrincipal,
            banco:                      $dto->banco,
            cuentaBancaria:             $dto->cuentaBancaria,
            observaciones:              $dto->observaciones,
            activo:                     $dto->activo,
        );

        return DB::transaction(fn() => $this->repository->save($supplier));
    }
}
