<?php

declare(strict_types=1);

namespace Fulll\Tests\Contract;

use Fulll\Domain\Exception\FleetModifiedConcurrently;
use Fulll\Domain\Exception\FleetNotFound;
use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\Location;
use Fulll\Domain\PlateNumber;
use Fulll\Domain\UserId;
use Fulll\Tests\Assertion\LocationAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Behavior every FleetRepository adapter must provide (Liskov substitution):
 * each adapter's test extends this class and only says how to build the adapter.
 */
abstract class FleetRepositoryContractTestCase extends TestCase
{
    use LocationAssertions;

    private FleetRepository $repository;

    /**
     * Must return an empty repository.
     */
    abstract protected function createRepository(): FleetRepository;

    protected function setUp(): void
    {
        $this->repository = $this->createRepository();
    }

    #[Test]
    public function itGetsASavedFleet(): void
    {
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));

        $this->repository->save($fleet);
        $found = $this->repository->get($fleet->id);

        self::assertTrue($found->id->equals($fleet->id));
        self::assertSame('john', $found->userId->value);
        self::assertSame([], $found->vehicles());
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
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $this->repository->save($fleet);
        $plate = new PlateNumber('AB-123-CD');

        $this->repository->get($fleet->id)->register($plate);

        self::assertFalse($this->repository->get($fleet->id)->hasVehicle($plate));
    }

    #[Test]
    public function itPersistsVehiclesWithTheirLocation(): void
    {
        $withAltitude = new Location(43.5283, 5.4497, 173.5);
        $withoutAltitude = new Location(-33.8688, 151.2093);
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $fleet->register(new PlateNumber('AB-123-CD'));
        $fleet->register(new PlateNumber('EF-456-GH'));
        $fleet->register(new PlateNumber('IJ-789-KL'));
        $fleet->park(new PlateNumber('AB-123-CD'), $withAltitude);
        $fleet->park(new PlateNumber('EF-456-GH'), $withoutAltitude);

        $this->repository->save($fleet);
        $found = $this->repository->get($fleet->id);

        self::assertCount(3, $found->vehicles());
        self::assertSameLocation($withAltitude, $found->locationOf(new PlateNumber('AB-123-CD')));
        self::assertSameLocation($withoutAltitude, $found->locationOf(new PlateNumber('EF-456-GH')));
        self::assertNull($found->locationOf(new PlateNumber('IJ-789-KL')));
    }

    #[Test]
    public function itPersistsANewLocationOfAVehicle(): void
    {
        $plate = new PlateNumber('AB-123-CD');
        $newLocation = new Location(43.2965, 5.3698);
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $fleet->register($plate);
        $fleet->park($plate, new Location(43.5283, 5.4497));
        $this->repository->save($fleet);

        $fleet = $this->repository->get($fleet->id);
        $fleet->park($plate, $newLocation);
        $this->repository->save($fleet);

        self::assertSameLocation($newLocation, $this->repository->get($fleet->id)->locationOf($plate));
    }

    #[Test]
    public function eachFleetKeepsItsOwnLocationOfASharedVehicle(): void
    {
        $plate = new PlateNumber('AB-123-CD');
        $myLocation = new Location(43.5283, 5.4497);
        $myFleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $otherFleet = Fleet::create(FleetId::generate(), new UserId('jane'));
        $myFleet->register($plate);
        $otherFleet->register($plate);
        $myFleet->park($plate, $myLocation);

        $this->repository->save($myFleet);
        $this->repository->save($otherFleet);

        self::assertSameLocation($myLocation, $this->repository->get($myFleet->id)->locationOf($plate));
        self::assertNull($this->repository->get($otherFleet->id)->locationOf($plate));
    }

    #[Test]
    public function itPersistsTheLongestValuesTheDomainAccepts(): void
    {
        // Multibyte on purpose: the storage must count characters like the domain does, not bytes.
        $plate = new PlateNumber(str_repeat('É', PlateNumber::MAX_LENGTH));
        $fleet = Fleet::create(FleetId::generate(), new UserId(str_repeat('é', UserId::MAX_LENGTH)));
        $fleet->register($plate);

        $this->repository->save($fleet);
        $found = $this->repository->get($fleet->id);

        self::assertSame($fleet->userId->value, $found->userId->value);
        self::assertTrue($found->hasVehicle($plate));
    }

    #[Test]
    public function aFleetCanBeSavedSeveralTimes(): void
    {
        $plate = new PlateNumber('AB-123-CD');
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $this->repository->save($fleet);

        $fleet->register($plate);
        $this->repository->save($fleet);
        $fleet->park($plate, new Location(43.5283, 5.4497));
        $this->repository->save($fleet);

        self::assertSameLocation(new Location(43.5283, 5.4497), $this->repository->get($fleet->id)->locationOf($plate));
    }

    #[Test]
    public function itRefusesToSaveAFleetSavedByOthersSinceItWasLoaded(): void
    {
        // Two commands load the same fleet at the same time, and change two different vehicles.
        $parkedPlate = new PlateNumber('AB-123-CD');
        $fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $fleet->register($parkedPlate);
        $fleet->park($parkedPlate, new Location(43.5283, 5.4497));
        $this->repository->save($fleet);
        $first = $this->repository->get($fleet->id);
        $second = $this->repository->get($fleet->id);

        $first->park($parkedPlate, new Location(43.2965, 5.3698));
        $this->repository->save($first);
        $second->register(new PlateNumber('EF-456-GH'));

        try {
            $this->repository->save($second);
            self::fail('A fleet saved by someone else since it was loaded must not be saved.');
        } catch (FleetModifiedConcurrently) {
        }

        $found = $this->repository->get($fleet->id);
        self::assertSameLocation(new Location(43.2965, 5.3698), $found->locationOf($parkedPlate));
        self::assertFalse($found->hasVehicle(new PlateNumber('EF-456-GH')));
    }

    #[Test]
    public function itRefusesToSaveANewFleetWithTheIdOfAnExistingOne(): void
    {
        $id = FleetId::generate();
        $this->repository->save(Fleet::create($id, new UserId('john')));

        $this->expectException(FleetModifiedConcurrently::class);

        $this->repository->save(Fleet::create($id, new UserId('jane')));
    }
}
