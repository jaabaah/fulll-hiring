<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Infra\InMemory;

use Fulll\Domain\Exception\FleetNotFound;
use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\PlateNumber;
use Fulll\Domain\UserId;
use Fulll\Infra\InMemory\InMemoryFleetRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryFleetRepositoryTest extends TestCase
{
    private InMemoryFleetRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryFleetRepository();
    }

    #[Test]
    public function itGetsASavedFleet(): void
    {
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));

        $this->repository->save($fleet);

        self::assertTrue($this->repository->get($fleet->id)->id->equals($fleet->id));
    }

    #[Test]
    public function itFailsToGetAnUnknownFleet(): void
    {
        $this->expectException(FleetNotFound::class);

        $this->repository->get(FleetId::generate());
    }

    #[Test]
    public function changesAreOnlyPersistedWhenTheFleetIsSaved(): void
    {
        // Behaves like a real database: a fleet modified but not saved must stay unchanged.
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $this->repository->save($fleet);
        $plate = new PlateNumber('AB-123-CD');

        $this->repository->get($fleet->id)->register($plate);

        self::assertFalse($this->repository->get($fleet->id)->hasVehicle($plate));
    }
}
