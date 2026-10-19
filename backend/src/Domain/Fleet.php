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

    /**
     * A brand new fleet, without any vehicle.
     */
    public static function create(FleetId $id, UserId $userId): self
    {
        return new self($id, $userId);
    }

    /**
     * An existing fleet, rebuilt from its persisted state (e.g. by a repository).
     *
     * @throws VehicleAlreadyRegistered
     */
    public static function reconstitute(FleetId $id, UserId $userId, Vehicle ...$vehicles): self
    {
        $fleet = new self($id, $userId);
        foreach ($vehicles as $vehicle) {
            $fleet->add($vehicle);
        }

        return $fleet;
    }

    /**
     * @throws VehicleAlreadyRegistered
     */
    public function register(PlateNumber $plate): void
    {
        $this->add(new Vehicle($plate));
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
     * @return list<Vehicle> in registration order
     */
    public function vehicles(): array
    {
        return array_values($this->vehicles);
    }

    /**
     * @throws VehicleAlreadyRegistered
     */
    private function add(Vehicle $vehicle): void
    {
        if ($this->hasVehicle($vehicle->plateNumber)) {
            throw VehicleAlreadyRegistered::withPlate($vehicle->plateNumber);
        }

        $this->vehicles[$vehicle->plateNumber->value] = $vehicle;
    }

    /**
     * @throws VehicleNotRegistered
     */
    private function vehicle(PlateNumber $plate): Vehicle
    {
        return $this->vehicles[$plate->value] ?? throw VehicleNotRegistered::withPlate($plate);
    }
}
