<?php

declare(strict_types=1);

namespace Fulll\Infra\Postgres;

use Fulll\Domain\Exception\FleetException;
use Fulll\Domain\Exception\FleetModifiedConcurrently;
use Fulll\Domain\Exception\FleetNotFound;
use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\Location;
use Fulll\Domain\PlateNumber;
use Fulll\Domain\UserId;
use Fulll\Domain\Vehicle;
use PDO;
use Throwable;
use UnexpectedValueException;
use WeakMap;

/**
 * Adapter persisting the Fleet aggregate into PostgreSQL (schema: database/schema.sql).
 */
final readonly class PostgresFleetRepository implements FleetRepository
{
    /**
     * Version read from the database for every fleet handed out by get() or saved (optimistic locking).
     *
     * @var WeakMap<Fleet, int>
     */
    private WeakMap $versions;

    public function __construct(
        private PDO $pdo,
    ) {
        $this->versions = new WeakMap();
    }

    /**
     * No transaction is needed around the two reads: if a save commits between them,
     * the version read is the old one, so the next save() of this fleet is refused.
     */
    public function get(FleetId $id): Fleet
    {
        $statement = $this->pdo->prepare('SELECT user_id, version FROM fleets WHERE id = :id');
        $statement->execute(['id' => $id->value]);
        $row = $statement->fetch(PDO::FETCH_NUM);

        if ($row === false) {
            throw FleetNotFound::withId($id);
        }

        [$userId, $version] = $row;

        if (!is_string($userId) || !is_int($version)) {
            throw new UnexpectedValueException(sprintf('Invalid row stored for fleet "%s".', $id->value));
        }

        $statement = $this->pdo->prepare(
            'SELECT plate_number, latitude, longitude, altitude FROM fleet_vehicles WHERE fleet_id = :id ORDER BY plate_number',
        );
        $statement->execute(['id' => $id->value]);

        try {
            $vehicles = [];
            foreach ($statement->fetchAll(PDO::FETCH_NUM) as $vehicleRow) {
                $vehicles[] = self::toVehicle($vehicleRow);
            }

            $fleet = Fleet::reconstitute($id, new UserId($userId), ...$vehicles);
        } catch (FleetException $error) {
            // The stored state breaks a domain rule: the data is corrupted, it is not a mistake of the user.
            throw new UnexpectedValueException(
                sprintf('Invalid state stored for fleet "%s": %s', $id->value, $error->getMessage()),
                previous: $error,
            );
        }

        $this->versions->offsetSet($fleet, $version);

        return $fleet;
    }

    /**
     * Saves the whole aggregate in a single transaction, provided nobody saved it since it was loaded.
     * Vehicles are never removed from a fleet, so inserting or updating them is enough.
     */
    public function save(Fleet $fleet): void
    {
        $this->pdo->beginTransaction();

        try {
            $version = $this->claimNextVersion($fleet);

            $upsertVehicle = $this->pdo->prepare(
                'INSERT INTO fleet_vehicles (fleet_id, plate_number, latitude, longitude, altitude)
                 VALUES (:fleet_id, :plate_number, :latitude, :longitude, :altitude)
                 ON CONFLICT (fleet_id, plate_number) DO UPDATE
                 SET latitude = EXCLUDED.latitude, longitude = EXCLUDED.longitude, altitude = EXCLUDED.altitude',
            );

            foreach ($fleet->vehicles() as $vehicle) {
                $upsertVehicle->execute([
                    'fleet_id' => $fleet->id->value,
                    'plate_number' => $vehicle->plateNumber->value,
                    'latitude' => $vehicle->location?->latitude,
                    'longitude' => $vehicle->location?->longitude,
                    'altitude' => $vehicle->location?->altitude,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            $this->pdo->rollBack();

            throw $error;
        }

        $this->versions->offsetSet($fleet, $version);
    }

    /**
     * Writes the fleet row first. It fails if the fleet was saved since it was loaded; otherwise the row stays
     * locked until the end of the transaction, so a concurrent save waits, then fails on the version check.
     *
     * @throws FleetModifiedConcurrently
     */
    private function claimNextVersion(Fleet $fleet): int
    {
        $loadedVersion = $this->versions[$fleet] ?? null;

        if ($loadedVersion === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO fleets (id, user_id, version) VALUES (:id, :user_id, 0) ON CONFLICT (id) DO NOTHING',
            );
            $statement->execute(['id' => $fleet->id->value, 'user_id' => $fleet->userId->value]);
        } else {
            $statement = $this->pdo->prepare('UPDATE fleets SET version = version + 1 WHERE id = :id AND version = :version');
            $statement->execute(['id' => $fleet->id->value, 'version' => $loadedVersion]);
        }

        if ($statement->rowCount() !== 1) {
            throw FleetModifiedConcurrently::withId($fleet->id);
        }

        return $loadedVersion === null ? 0 : $loadedVersion + 1;
    }

    /**
     * Database values are untrusted at the type level: they are checked before building the domain objects.
     */
    private static function toVehicle(mixed $row): Vehicle
    {
        if (!is_array($row)) {
            throw new UnexpectedValueException('Invalid vehicle row.');
        }

        [$plateNumber, $latitude, $longitude, $altitude] = $row;

        if (!is_string($plateNumber)) {
            throw new UnexpectedValueException('Invalid plate number stored.');
        }

        if ($latitude === null && $longitude === null) {
            return new Vehicle(new PlateNumber($plateNumber));
        }

        if (!is_float($latitude) || !is_float($longitude) || !(is_float($altitude) || $altitude === null)) {
            throw new UnexpectedValueException(sprintf('Invalid location stored for vehicle "%s".', $plateNumber));
        }

        return new Vehicle(new PlateNumber($plateNumber), new Location($latitude, $longitude, $altitude));
    }
}
