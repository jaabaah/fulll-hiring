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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Vehicle is an entity internal to the Fleet aggregate:
 * it is tested through its root, the only entry point of the aggregate.
 */
final class FleetTest extends TestCase
{
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

        self::assertTrue($location->equals($this->fleet->locationOf($this->plate)));
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

        self::assertTrue($home->equals($this->fleet->locationOf($this->plate)));
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
}
