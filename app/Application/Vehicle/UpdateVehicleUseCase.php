<?php

declare(strict_types=1);

namespace App\Application\Vehicle;

use App\Domain\Vehicle\Processors\VehicleImageProcessor;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Domain\Vehicle\Validators\VehicleIntegrityValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleIntegrityValidator $validator,
        private readonly VehicleImageProcessor $imageProcessor
    ) {}

    public function execute(int $id, array $data, ?array $imagenes = null): bool
    {
        $this->validator->validateForUpdate($id, $data);

        $data['updated_by'] = Auth::id();
        $data['updated_at'] = now();

        return DB::transaction(function () use ($id, $data, $imagenes) {
            $updated = $this->vehicleRepository->update($id, $data);

            if (!empty($imagenes)) {
                $this->imageProcessor->appendMore($id, $imagenes);
            }

            return $updated;
        });
    }
}
