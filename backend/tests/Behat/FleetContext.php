<?php

declare(strict_types=1);

namespace Fulll\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use DomainException;
use Fulll\App\Command\CreateFleet;
use Fulll\App\Command\CreateFleetHandler;
use Fulll\App\Command\ParkVehicle;
use Fulll\App\Command\ParkVehicleHandler;
use Fulll\App\Command\RegisterVehicle;
use Fulll\App\Command\RegisterVehicleHandler;
use Fulll\App\Query\GetVehicleLocation;
use Fulll\App\Query\GetVehicleLocationHandler;
use Fulll\App\Query\IsVehicleRegistered;
use Fulll\App\Query\IsVehicleRegisteredHandler;
use Fulll\Domain\Exception\VehicleAlreadyParkedHere;
use Fulll\Domain\Exception\VehicleAlreadyRegistered;
use Fulll\Domain\FleetRepository;
use Fulll\Infra\InMemory\InMemoryFleetRepository;
use RuntimeException;

/**
 * Drives the application only through its command and query handlers,
 * exactly like any other entry point (e.g. the CLI) would.
 */
final class FleetContext implements Context
{
    private readonly FleetRepository $fleets;

    private string $myFleetId;
    private string $otherUserFleetId;
    private string $plateNumber;
    private float $latitude;
    private float $longitude;
    private ?DomainException $caughtError = null;

    public function __construct()
    {
        // Behat creates a new context for each scenario: every scenario starts with an empty repository.
        $this->fleets = new InMemoryFleetRepository();
    }

    #[Given('my fleet')]
    public function myFleet(): void
    {
        $this->myFleetId = $this->createFleet('my-user');
    }

    #[Given('the fleet of another user')]
    public function theFleetOfAnotherUser(): void
    {
        $this->otherUserFleetId = $this->createFleet('another-user');
    }

    #[Given('a vehicle')]
    public function aVehicle(): void
    {
        $this->plateNumber = 'AB-123-CD';
    }

    #[Given('a location')]
    public function aLocation(): void
    {
        // Aix-en-Provence
        $this->latitude = 43.5283;
        $this->longitude = 5.4497;
    }

    #[Given('I have registered this vehicle into my fleet')]
    #[When('I register this vehicle into my fleet')]
    public function iRegisterThisVehicleIntoMyFleet(): void
    {
        $this->registerVehicle($this->myFleetId);
    }

    #[Given("this vehicle has been registered into the other user's fleet")]
    public function thisVehicleHasBeenRegisteredIntoTheOtherUsersFleet(): void
    {
        $this->registerVehicle($this->otherUserFleetId);
    }

    #[When('I try to register this vehicle into my fleet')]
    public function iTryToRegisterThisVehicleIntoMyFleet(): void
    {
        $this->attempt(fn () => $this->registerVehicle($this->myFleetId));
    }

    #[Given('my vehicle has been parked into this location')]
    #[When('I park my vehicle at this location')]
    public function iParkMyVehicleAtThisLocation(): void
    {
        new ParkVehicleHandler($this->fleets)(
            new ParkVehicle($this->myFleetId, $this->plateNumber, $this->latitude, $this->longitude),
        );
    }

    #[When('I try to park my vehicle at this location')]
    public function iTryToParkMyVehicleAtThisLocation(): void
    {
        $this->attempt($this->iParkMyVehicleAtThisLocation(...));
    }

    #[Then('this vehicle should be part of my vehicle fleet')]
    public function thisVehicleShouldBePartOfMyVehicleFleet(): void
    {
        $isRegistered = new IsVehicleRegisteredHandler($this->fleets)(
            new IsVehicleRegistered($this->myFleetId, $this->plateNumber),
        );

        self::assert($isRegistered, 'The vehicle is not part of my fleet.');
    }

    #[Then('I should be informed this this vehicle has already been registered into my fleet')]
    public function iShouldBeInformedThisVehicleHasAlreadyBeenRegistered(): void
    {
        self::assert(
            $this->caughtError instanceof VehicleAlreadyRegistered,
            'I was not informed that the vehicle has already been registered.',
        );
    }

    #[Then('the known location of my vehicle should verify this location')]
    public function theKnownLocationOfMyVehicleShouldVerifyThisLocation(): void
    {
        $location = new GetVehicleLocationHandler($this->fleets)(
            new GetVehicleLocation($this->myFleetId, $this->plateNumber),
        );

        self::assert(
            $location?->latitude === $this->latitude && $location->longitude === $this->longitude,
            'The known location of my vehicle does not match.',
        );
    }

    #[Then('I should be informed that my vehicle is already parked at this location')]
    public function iShouldBeInformedThatMyVehicleIsAlreadyParkedAtThisLocation(): void
    {
        self::assert(
            $this->caughtError instanceof VehicleAlreadyParkedHere,
            'I was not informed that my vehicle is already parked at this location.',
        );
    }

    private function createFleet(string $userId): string
    {
        return new CreateFleetHandler($this->fleets)(new CreateFleet($userId))->value;
    }

    private function registerVehicle(string $fleetId): void
    {
        new RegisterVehicleHandler($this->fleets)(new RegisterVehicle($fleetId, $this->plateNumber));
    }

    /**
     * Runs an action expected to be refused by the domain, and keeps the error for the "Then" step.
     */
    private function attempt(callable $action): void
    {
        try {
            $action();
        } catch (DomainException $error) {
            $this->caughtError = $error;
        }
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
}
