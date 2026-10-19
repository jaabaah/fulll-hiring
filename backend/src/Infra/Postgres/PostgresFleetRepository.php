<?php

declare(strict_types=1);

namespace Fulll\Infra\Postgres;

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

/**
 * Adapter persisting the Fleet aggregate into PostgreSQL (schema: database/schema.sql).
 */
final readonly class PostgresFleetRepository implements FleetRepository
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function get(FleetId $id): Fleet
    {
        $statement = $this->pdo->prepare('SELECT user_id FROM fleets WHERE id = :id');
        $statement->execute(['id' => $id->value]);
        $userId = $statement->fetchColumn();

        if ($userId === false) {
            throw FleetNotFound::withId($id);
        }

        $statement = $this->pdo->prepare(
            'SELECT plate_number, latitude, longitude, altitude FROM fleet_vehicles WHERE fleet_id = :id ORDER BY plate_number',
        );
        $statement->execute(['id' => $id->value]);

        $vehicles = array_map(self::toVehicle(...), $statement->fetchAll());

        return Fleet::reconstitute($id, new UserId($userId), ...$vehicles);
    }

    /**
     * Saves the whole aggregate in a single transaction.
     * Vehicles are never removed from a fleet, so inserting or updating them is enough.
     */
    public function save(Fleet $fleet): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->pdo
                ->prepare('INSERT INTO fleets (id, user_id) VALUES (:id, :user_id) ON CONFLICT (id) DO NOTHING')
                ->execute(['id' => $fleet->id->value, 'user_id' => $fleet->userId->value]);

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
    }

    /**
     * @param array{plate_number: string, latitude: ?float, longitude: ?float, altitude: ?float} $row
     */
    private static function toVehicle(array $row): Vehicle
    {
        $location = $row['latitude'] === null
            ? null
            : new Location($row['latitude'], $row['longitude'], $row['altitude']);

        return new Vehicle(new PlateNumber($row['plate_number']), $location);
    }
}
