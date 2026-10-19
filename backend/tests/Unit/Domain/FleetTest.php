<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Domain;

use Fulll\Domain\Exception\VehicleAlreadyParkedHere;
use Fulll\Domain\Exception\VehicleAlreadyRegistered;
use Fulll\Domain\Exception\VehicleNotRegistered;
use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\Location;
use Fulll\Domain\PlateNumber;
use Fulll\Domain\UserId;
use Fulll\Domain\Vehicle;
use Fulll\Tests\Assertion\LocationAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Vehicle is an entity internal to the Fleet aggregate:
 * it is tested through its root, the only entry point of the aggregate.
 */
final class FleetTest extends TestCase
{
    use LocationAssertions;

    private Fleet $fleet;
    private PlateNumber $plate;

    protected function setUp(): void
    {
        $this->fleet = Fleet::create(FleetId::generate(), new UserId('john'));
        $this->plate = new PlateNumber('AB-123-CD');
    }

    #[Test]
    public function aNewFleetBelongsToItsUserAndHasNoVehicle(): void
    {
        self::assertSame('john', $this->fleet->userId->value);
        self::assertFalse($this->fleet->hasVehicle($this->plate));
    }

    #[Test]
    public function itRegistersAVehicle(): void
    {
        $this->fleet->register($this->plate);

        self::assertTrue($this->fleet->hasVehicle($this->plate));
    }

    #[Test]
    public function itRefusesToRegisterTheSameVehicleTwice(): void
    {
        $this->fleet->register($this->plate);

        $this->expectException(VehicleAlreadyRegistered::class);

        $this->fleet->register(new PlateNumber('ab-123-cd'));
    }

    #[Test]
    public function aRegisteredVehicleHasNoKnownLocationYet(): void
    {
        $this->fleet->register($this->plate);

        self::assertNull($this->fleet->locationOf($this->plate));
    }

    #[Test]
    public function itParksARegisteredVehicle(): void
    {
        $location = new Location(43.5283, 5.4497);
        $this->fleet->register($this->plate);

        $this->fleet->park($this->plate, $location);

        self::assertSameLocation($location, $this->fleet->locationOf($this->plate));
    }

    #[Test]
    public function itRefusesToParkAVehicleTwiceInARowAtTheSameLocation(): void
    {
        $this->fleet->register($this->plate);
        $this->fleet->park($this->plate, new Location(43.5283, 5.4497));

        $this->expectException(VehicleAlreadyParkedHere::class);

        $this->fleet->park($this->plate, new Location(43.5283, 5.4497));
    }

    #[Test]
    public function itParksAVehicleBackToAPreviousLocationAfterMovingIt(): void
    {
        $home = new Location(43.5283, 5.4497);
        $this->fleet->register($this->plate);
        $this->fleet->park($this->plate, $home);
        $this->fleet->park($this->plate, new Location(43.2965, 5.3698));

        $this->fleet->park($this->plate, $home);

        self::assertSameLocation($home, $this->fleet->locationOf($this->plate));
    }

    #[Test]
    public function itRefusesToParkAnUnregisteredVehicle(): void
    {
        $this->expectException(VehicleNotRegistered::class);

        $this->fleet->park($this->plate, new Location(43.5283, 5.4497));
    }

    #[Test]
    public function itCannotLocateAnUnregisteredVehicle(): void
    {
        $this->expectException(VehicleNotRegistered::class);

        $this->fleet->locationOf($this->plate);
    }

    #[Test]
    public function itListsItsVehiclesWithTheirLocation(): void
    {
        $location = new Location(43.5283, 5.4497);
        $otherPlate = new PlateNumber('EF-456-GH');
        $this->fleet->register($this->plate);
        $this->fleet->register($otherPlate);
        $this->fleet->park($this->plate, $location);

        $vehicles = $this->fleet->vehicles();

        self::assertCount(2, $vehicles);
        self::assertTrue($vehicles[0]->plateNumber->equals($this->plate));
        self::assertSameLocation($location, $vehicles[0]->location);
        self::assertTrue($vehicles[1]->plateNumber->equals($otherPlate));
        self::assertNull($vehicles[1]->location);
    }

    #[Test]
    public function itIsReconstitutedFromAPersistedState(): void
    {
        $id = FleetId::generate();
        $location = new Location(43.5283, 5.4497, 173.0);

        $fleet = Fleet::reconstitute($id, new UserId('john'), new Vehicle($this->plate, $location));

        self::assertTrue($fleet->id->equals($id));
        self::assertSame('john', $fleet->userId->value);
        self::assertSameLocation($location, $fleet->locationOf($this->plate));
    }

    #[Test]
    public function aReconstitutedFleetStillEnforcesItsRules(): void
    {
        $location = new Location(43.5283, 5.4497);
        $fleet = Fleet::reconstitute(FleetId::generate(), new UserId('john'), new Vehicle($this->plate, $location));

        $this->expectException(VehicleAlreadyParkedHere::class);

        $fleet->park($this->plate, $location);
    }

    #[Test]
    public function itCannotBeReconstitutedWithTheSameVehicleTwice(): void
    {
        $this->expectException(VehicleAlreadyRegistered::class);

        Fleet::reconstitute(
            FleetId::generate(),
            new UserId('john'),
            new Vehicle($this->plate),
            new Vehicle(new PlateNumber('ab-123-cd')),
        );
    }
}
