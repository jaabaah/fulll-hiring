<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Infra\InMemory;

use Fulll\Domain\FleetRepository;
use Fulll\Infra\InMemory\InMemoryFleetRepository;
use Fulll\Tests\Contract\FleetRepositoryContractTestCase;

final class InMemoryFleetRepositoryTest extends FleetRepositoryContractTestCase
{
    protected function createRepository(): FleetRepository
    {
        return new InMemoryFleetRepository();
    }
}
