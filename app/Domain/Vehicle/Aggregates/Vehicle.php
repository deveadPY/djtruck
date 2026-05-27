<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Aggregates;

use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Vehicle\Exceptions\InvalidVehicleDataException;

/**
 * Vehicle Aggregate Root.
 *
 * Guardian de invariantes del vehículo:
 * - Chasis no vacío
 * - Año dentro de rango válido (1950 .. año actual + 1)
 * - Costo de origen no negativo
 * - Estado en transiciones válidas
 */
final class Vehicle
{
    private const MIN_YEAR = 1950;
    private const VALID_STATES = ['DISPONIBLE', 'RESERVADO', 'VENDIDO', 'EN_PREPARACION', 'TOMA'];

    private ?VehicleId $id;
    private string $numeroChasis;
    private string $marca;
    private string $modelo;
    private int $anio;
    private string $tipoVehiculo;
    private string $estado;
    private Money $costoOrigen;
    private Money $gastosAcumulados;

    private function __construct(
        string $numeroChasis,
        string $marca,
        string $modelo,
        int $anio,
        string $tipoVehiculo,
        string $estado,
        Money $costoOrigen,
        Money $gastosAcumulados
    ) {
        $this->id = null;
        $this->numeroChasis = $numeroChasis;
        $this->marca = $marca;
        $this->modelo = $modelo;
        $this->anio = $anio;
        $this->tipoVehiculo = $tipoVehiculo;
        $this->estado = $estado;
        $this->costoOrigen = $costoOrigen;
        $this->gastosAcumulados = $gastosAcumulados;
    }

    public static function register(
        string $numeroChasis,
        string $marca,
        string $modelo,
        int $anio,
        string $tipoVehiculo,
        Money $costoOrigen,
        string $estado = 'DISPONIBLE'
    ): self {
        self::ensureChassisValid($numeroChasis);
        self::ensureYearValid($anio);
        self::ensureCostNonNegative($costoOrigen);
        self::ensureRequiredField('marca', $marca);
        self::ensureRequiredField('modelo', $modelo);

        return new self(
            $numeroChasis,
            $marca,
            $modelo,
            $anio,
            $tipoVehiculo,
            $estado,
            $costoOrigen,
            Money::zero($costoOrigen->currency)
        );
    }

    public function withId(VehicleId $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function addExpense(Money $amount): void
    {
        if ($amount->isNegative()) {
            throw InvalidVehicleDataException::negativeCost($amount->amount);
        }
        $this->gastosAcumulados = $this->gastosAcumulados->add($amount);
    }

    public function bookValue(): Money
    {
        return $this->costoOrigen->add($this->gastosAcumulados);
    }

    public function isAvailableForSale(): bool
    {
        return in_array($this->estado, ['DISPONIBLE', 'RESERVADO'], true);
    }

    public function markAsSold(): void
    {
        if (!$this->isAvailableForSale()) {
            throw new InvalidVehicleDataException(
                "Vehículo en estado '{$this->estado}' no puede marcarse como VENDIDO."
            );
        }
        $this->estado = 'VENDIDO';
    }

    private static function ensureChassisValid(string $chassis): void
    {
        if (trim($chassis) === '') {
            throw InvalidVehicleDataException::invalidChassis($chassis);
        }
    }

    private static function ensureYearValid(int $year): void
    {
        $maxYear = (int) date('Y') + 1;
        if ($year < self::MIN_YEAR || $year > $maxYear) {
            throw InvalidVehicleDataException::invalidYear($year);
        }
    }

    private static function ensureCostNonNegative(Money $cost): void
    {
        if ($cost->isNegative()) {
            throw InvalidVehicleDataException::negativeCost($cost->amount);
        }
    }

    private static function ensureRequiredField(string $name, string $value): void
    {
        if (trim($value) === '') {
            throw InvalidVehicleDataException::missingRequiredField($name);
        }
    }

    public function getId(): ?VehicleId        { return $this->id; }
    public function getNumeroChasis(): string  { return $this->numeroChasis; }
    public function getMarca(): string         { return $this->marca; }
    public function getModelo(): string        { return $this->modelo; }
    public function getAnio(): int             { return $this->anio; }
    public function getTipoVehiculo(): string  { return $this->tipoVehiculo; }
    public function getEstado(): string        { return $this->estado; }
    public function getCostoOrigen(): Money    { return $this->costoOrigen; }
    public function getGastosAcumulados(): Money { return $this->gastosAcumulados; }
}
