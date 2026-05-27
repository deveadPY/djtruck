<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Validators;

use App\Domain\Vehicle\Exceptions\DuplicateChassisException;
use App\Domain\Vehicle\Exceptions\InvalidVehicleDataException;
use Illuminate\Support\Facades\DB;

class VehicleIntegrityValidator
{
    private const MIN_YEAR = 1950;

    public function validateForCreate(array $data): void
    {
        $this->validateChassis((string) ($data['numero_chasis'] ?? ''));
        $this->ensureChassisUnique((string) $data['numero_chasis']);
        $this->validateYear((int) ($data['anio'] ?? 0));
        $this->validateCost((float) ($data['costo_origen_usd'] ?? 0));
    }

    public function validateForUpdate(int $vehicleId, array $data): void
    {
        if (isset($data['numero_chasis'])) {
            $this->validateChassis($data['numero_chasis']);
            $this->ensureChassisUnique($data['numero_chasis'], $vehicleId);
        }

        if (isset($data['anio'])) {
            $this->validateYear((int) $data['anio']);
        }

        if (isset($data['costo_origen_usd'])) {
            $this->validateCost((float) $data['costo_origen_usd']);
        }
    }

    private function validateChassis(string $chassis): void
    {
        if (trim($chassis) === '') {
            throw InvalidVehicleDataException::invalidChassis($chassis);
        }
    }

    private function ensureChassisUnique(string $chassis, ?int $excludeId = null): void
    {
        $query = DB::table('vehiculos')
            ->where('numero_chasis', $chassis)
            ->whereNull('deleted_at');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new DuplicateChassisException($chassis);
        }
    }

    private function validateYear(int $year): void
    {
        $maxYear = (int) date('Y') + 1;
        if ($year < self::MIN_YEAR || $year > $maxYear) {
            throw InvalidVehicleDataException::invalidYear($year);
        }
    }

    private function validateCost(float $cost): void
    {
        if ($cost < 0) {
            throw InvalidVehicleDataException::negativeCost($cost);
        }
    }
}
