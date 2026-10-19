<?php

declare(strict_types=1);

namespace Fulll\Tests\Integration\Infra\Postgres;

use Fulll\Domain\FleetRepository;
use Fulll\Infra\Postgres\PdoFactory;
use Fulll\Infra\Postgres\PostgresFleetRepository;
use Fulll\Tests\Contract\FleetRepositoryContractTestCase;

/**
 * Runs the repository contract against a real PostgreSQL database (see compose.yaml).
 */
final class PostgresFleetRepositoryTest extends FleetRepositoryContractTestCase
{
    protected function createRepository(): FleetRepository
    {
        $pdo = PdoFactory::fromUrl((string) getenv('TEST_DATABASE_URL'));
        $pdo->exec('TRUNCATE fleets CASCADE');

        return new PostgresFleetRepository($pdo);
    }
}
