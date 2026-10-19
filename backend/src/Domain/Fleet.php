<?php

declare(strict_types=1);

namespace Fulll\Domain;

use Fulll\Domain\Exception\VehicleAlreadyParkedHere;
use Fulll\Domain\Exception\VehicleAlreadyRegistered;
use Fulll\Domain\Exception\VehicleNotRegistered;

/**
 * Aggregate root: the only entry point to the vehicles of a fleet,
 * guarding the invariants on them (no duplicate vehicle, no parking twice in a row at the same location).
 */
final class Fleet
{
    /** @var array<string, Vehicle> indexed by plate number */
    private array $vehicles = [];

    private function __construct(
        public readonly FleetId $id,
        public readonly UserId $userId,
    ) {
    }

    public static function create(FleetId $id, UserId $userId): self
    {
        return new self($id, $userId);
    }

    /**
     * @throws VehicleAlreadyRegistered
     */
    public function register(PlateNumber $plate): void
    {
        if ($this->hasVehicle($plate)) {
            throw VehicleAlreadyRegistered::withPlate($plate);
        }

        $this->vehicles[$plate->value] = new Vehicle($plate);
    }

    /**
     * @throws VehicleNotRegistered
     * @throws VehicleAlreadyParkedHere
     */
    public function park(PlateNumber $plate, Location $location): void
    {
        $this->vehicles[$plate->value] = $this->vehicle($plate)->parkAt($location);
    }

    public function hasVehicle(PlateNumber $plate): bool
    {
        return isset($this->vehicles[$plate->value]);
    }

    /**
     * @return Location|null null if the vehicle has never been parked
     *
     * @throws VehicleNotRegistered
     */
    public function locationOf(PlateNumber $plate): ?Location
    {
        return $this->vehicle($plate)->location;
    }

    /**
     * @throws VehicleNotRegistered
     */
    private function vehicle(PlateNumber $plate): Vehicle
    {
        return $this->vehicles[$plate->value] ?? throw VehicleNotRegistered::withPlate($plate);
    }
}
