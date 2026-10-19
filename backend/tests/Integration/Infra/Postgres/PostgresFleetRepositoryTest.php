<?php

declare(strict_types=1);

namespace Fulll\Tests\Integration\Infra\Postgres;

use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\UserId;
use Fulll\Infra\Postgres\PdoFactory;
use Fulll\Infra\Postgres\PostgresFleetRepository;
use Fulll\Tests\Contract\FleetRepositoryContractTestCase;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use UnexpectedValueException;

/**
 * Runs the repository contract against a real PostgreSQL database (see compose.yaml).
 */
final class PostgresFleetRepositoryTest extends FleetRepositoryContractTestCase
{
    private PDO $pdo;

    protected function createRepository(): FleetRepository
    {
        $this->pdo = PdoFactory::fromUrl((string) getenv('TEST_DATABASE_URL'));
        $this->pdo->exec('TRUNCATE fleets CASCADE');

        return new PostgresFleetRepository($this->pdo);
    }

    #[Test]
    public function aStoredStateBreakingADomainRuleIsReportedAsCorruptedData(): void
    {
        // Written outside the application: two rows that the domain normalizes to the same plate number.
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $repository = new PostgresFleetRepository($this->pdo);
        $repository->save($fleet);
        $this->pdo
            ->prepare("INSERT INTO fleet_vehicles (fleet_id, plate_number) VALUES (:id, 'ab-123-cd'), (:id, 'AB-123-CD')")
            ->execute(['id' => $fleet->id->value]);

        // Not VehicleAlreadyRegistered: the user did nothing wrong, the CLI must not present it as a refused rule.
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid state stored for fleet');

        $repository->get($fleet->id);
    }
}
