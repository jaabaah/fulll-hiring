<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Infra\Cli;

use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\Location;
use Fulll\Domain\PlateNumber;
use Fulll\Infra\Cli\FleetApplication;
use Fulll\Infra\InMemory\InMemoryFleetRepository;
use Fulll\Tests\Assertion\LocationAssertions;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Runs the real CLI application, wired on the in-memory repository.
 */
final class FleetApplicationTest extends TestCase
{
    use LocationAssertions;

    private InMemoryFleetRepository $fleets;
    private ApplicationTester $cli;

    protected function setUp(): void
    {
        $this->fleets = new InMemoryFleetRepository();
        $application = FleetApplication::create($this->fleets);
        $application->setAutoExit(false);
        $this->cli = new ApplicationTester($application);
    }

    #[Test]
    public function createPrintsTheIdOfTheNewFleet(): void
    {
        $exitCode = $this->runCli(['command' => 'create', 'user-id' => 'john']);

        self::assertSame(Command::SUCCESS, $exitCode);
        $fleetId = new FleetId(trim($this->cli->getDisplay()));
        self::assertSame('john', $this->fleets->get($fleetId)->userId->value);
    }

    #[Test]
    public function registerVehicleRegistersTheVehicleIntoTheFleet(): void
    {
        $fleetId = $this->createFleet();

        $exitCode = $this->runCli(['command' => 'register-vehicle', 'fleet-id' => $fleetId, 'vehicle-plate-number' => 'AB-123-CD']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertTrue($this->fleets->get(new FleetId($fleetId))->hasVehicle(new PlateNumber('AB-123-CD')));
    }

    #[Test]
    public function registerVehicleFailsWhenTheVehicleIsAlreadyRegistered(): void
    {
        $fleetId = $this->createFleet();
        $this->registerVehicle($fleetId);

        $exitCode = $this->runCli(['command' => 'register-vehicle', 'fleet-id' => $fleetId, 'vehicle-plate-number' => 'AB-123-CD']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('already been registered', $this->cli->getErrorOutput());
    }

    #[Test]
    public function registerVehicleFailsWhenTheFleetDoesNotExist(): void
    {
        $exitCode = $this->runCli(['command' => 'register-vehicle', 'fleet-id' => FleetId::generate()->value, 'vehicle-plate-number' => 'AB-123-CD']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('not found', $this->cli->getErrorOutput());
    }

    #[Test]
    public function registerVehicleFailsWhenTheFleetIdIsInvalid(): void
    {
        $exitCode = $this->runCli(['command' => 'register-vehicle', 'fleet-id' => 'not-a-uuid', 'vehicle-plate-number' => 'AB-123-CD']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('not a valid fleet id', $this->cli->getErrorOutput());
    }

    #[Test]
    public function registerVehicleFailsWhenThePlateNumberIsTooLong(): void
    {
        $fleetId = $this->createFleet();

        $exitCode = $this->runCli(['command' => 'register-vehicle', 'fleet-id' => $fleetId, 'vehicle-plate-number' => str_repeat('A', 33)]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('at most 32 printable characters', $this->cli->getErrorOutput());
    }

    #[Test]
    public function anUnexpectedErrorIsNotPresentedAsARefusedRule(): void
    {
        // An SPL exception that does not come from the fleet model is a bug: it keeps the default handling.
        $failingRepository = new class implements FleetRepository {
            public function get(FleetId $id): Fleet
            {
                throw new InvalidArgumentException('A bug, not a mistake of the user.');
            }

            public function save(Fleet $fleet): void {}
        };
        $application = FleetApplication::create($failingRepository);
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $this->expectException(InvalidArgumentException::class);

        new ApplicationTester($application)->run(['command' => 'register-vehicle', 'fleet-id' => FleetId::generate()->value, 'vehicle-plate-number' => 'AB-123-CD']);
    }

    #[Test]
    public function localizeVehicleParksTheVehicleWithAnOptionalAltitude(): void
    {
        $fleetId = $this->createFleet();
        $this->registerVehicle($fleetId);

        $exitCode = $this->runCli([
            'command' => 'localize-vehicle',
            'fleet-id' => $fleetId,
            'vehicle-plate-number' => 'AB-123-CD',
            'lat' => '43.5283',
            'lng' => '5.4497',
            'alt' => '173.5',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSameLocation(new Location(43.5283, 5.4497, 173.5), $this->locationOf($fleetId));
    }

    #[Test]
    public function localizeVehicleAcceptsNegativeCoordinatesFromTheCommandLine(): void
    {
        // Real argv parsing: "-33.8688" must not be mistaken for an option.
        $fleetId = $this->createFleet();
        $this->registerVehicle($fleetId);
        $application = FleetApplication::create($this->fleets);
        $application->setAutoExit(false);

        $previousArgv = $_SERVER['argv'];
        $_SERVER['argv'] = ['fleet', 'localize-vehicle', $fleetId, 'AB-123-CD', '-33.8688', '151.2093'];

        try {
            $exitCode = $application->run(output: new BufferedOutput());
        } finally {
            $_SERVER['argv'] = $previousArgv;
        }

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSameLocation(new Location(-33.8688, 151.2093), $this->locationOf($fleetId));
    }

    #[Test]
    public function localizeVehicleFailsWhenACoordinateIsNotANumber(): void
    {
        $fleetId = $this->createFleet();
        $this->registerVehicle($fleetId);

        $exitCode = $this->runCli(['command' => 'localize-vehicle', 'fleet-id' => $fleetId, 'vehicle-plate-number' => 'AB-123-CD', 'lat' => 'north', 'lng' => '5.4497']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('must be a number', $this->cli->getErrorOutput());
    }

    #[Test]
    public function localizeVehicleFailsWhenACoordinateIsOutOfRange(): void
    {
        $fleetId = $this->createFleet();
        $this->registerVehicle($fleetId);

        $exitCode = $this->runCli(['command' => 'localize-vehicle', 'fleet-id' => $fleetId, 'vehicle-plate-number' => 'AB-123-CD', 'lat' => '95', 'lng' => '5.4497']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Latitude must be between', $this->cli->getErrorOutput());
    }

    #[Test]
    public function localizeVehicleFailsWhenTheVehicleIsAlreadyParkedThere(): void
    {
        $fleetId = $this->createFleet();
        $this->registerVehicle($fleetId);
        $arguments = ['command' => 'localize-vehicle', 'fleet-id' => $fleetId, 'vehicle-plate-number' => 'AB-123-CD', 'lat' => '43.5283', 'lng' => '5.4497'];
        $this->runCli($arguments);

        $exitCode = $this->runCli($arguments);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('already parked', $this->cli->getErrorOutput());
    }

    /**
     * @param array<string, string> $input
     */
    private function runCli(array $input): int
    {
        return $this->cli->run($input, ['capture_stderr_separately' => true]);
    }

    private function createFleet(): string
    {
        $this->runCli(['command' => 'create', 'user-id' => 'john']);

        return trim($this->cli->getDisplay());
    }

    private function registerVehicle(string $fleetId): void
    {
        $this->runCli(['command' => 'register-vehicle', 'fleet-id' => $fleetId, 'vehicle-plate-number' => 'AB-123-CD']);
    }

    private function locationOf(string $fleetId): ?Location
    {
        return $this->fleets->get(new FleetId($fleetId))->locationOf(new PlateNumber('AB-123-CD'));
    }
}
