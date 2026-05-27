<?php

declare(strict_types=1);

namespace App\Application\Customers;

use App\Domain\Customers\Aggregates\Customer;
use App\Domain\Customers\Repositories\CustomerRepositoryInterface;
use App\Domain\Customers\Validators\UniqueEmailValidator;
use App\Domain\Customers\Validators\UniqueRucValidator;
use App\Domain\Customers\ValueObjects\Ruc;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UpdateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
        private readonly UniqueRucValidator $rucValidator,
        private readonly UniqueEmailValidator $emailValidator,
    ) {}

    public function execute(UpdateCustomerDTO $dto): Customer
    {
        $existing = $this->repository->findById($dto->id);
        if (!$existing) {
            throw new RuntimeException("Cliente {$dto->id} no encontrado.");
        }

        $ruc = Ruc::parse($dto->ruc);

        $this->rucValidator->validate($ruc->value(), $dto->id);
        $this->emailValidator->validate($dto->email, $dto->id);

        $customer = Customer::create(
            ruc:             $ruc,
            razonSocial:     $dto->razonSocial,
            nombreFantasia:  $dto->nombreFantasia,
            pais:            $dto->pais,
            email:           $dto->email,
            telefono:        $dto->telefono,
            direccion:       $dto->direccion,
            lineaCreditoUsd: $dto->lineaCreditoUsd,
            activo:          $dto->activo,
        );

        return DB::transaction(fn() => $this->repository->update($dto->id, $customer));
    }
}
