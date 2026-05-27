<?php

declare(strict_types=1);

namespace App\Application\Vehicle;

use App\Domain\Vehicle\Calculator\VehicleBookValueCalculator;
use App\Domain\Vehicle\Events\VehicleRegistered;
use App\Domain\Vehicle\Processors\VehicleImageProcessor;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Domain\Vehicle\Validators\VehicleIntegrityValidator;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class CreateVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleIntegrityValidator $validator,
        private readonly VehicleImageProcessor $imageProcessor,
        private readonly VehicleBookValueCalculator $bookValueCalculator
    ) {}

    public function execute(CreateVehicleDTO $dto): VehicleModel
    {
        $data = $this->buildVehicleData($dto);

        $this->validator->validateForCreate($data);

        $vehicle = DB::transaction(function () use ($data, $dto) {
            $vehicle = $this->vehicleRepository->create($data);
            $this->imageProcessor->process((int) $vehicle->id, $dto->imagenes ?? []);
            return $vehicle;
        });

        Event::dispatch(new VehicleRegistered(
            vehicleId:     (int) $vehicle->id,
            chasis:        (string) $vehicle->numero_chasis,
            costOriginUsd: (float) ($vehicle->costo_origen_usd ?? 0),
        ));

        return $vehicle;
    }

    private function buildVehicleData(CreateVehicleDTO $dto): array
    {
        $data = $dto->toArray();
        $data['created_by'] = Auth::id();
        $data['total_gastos_usd'] = 0;

        return $data;
    }
}
