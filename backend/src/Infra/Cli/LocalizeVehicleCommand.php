<?php

declare(strict_types=1);

namespace Fulll\Infra\Cli;

use Fulll\App\Command\ParkVehicle;
use Fulll\App\Command\ParkVehicleHandler;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'localize-vehicle', description: 'Indicates where a vehicle of a fleet is parked')]
final readonly class LocalizeVehicleCommand
{
    public function __construct(
        private ParkVehicleHandler $parkVehicle,
    ) {
    }

    public function __invoke(
        #[Argument(description: 'Id of the fleet')] string $fleetId,
        #[Argument(description: 'Plate number of the vehicle')] string $vehiclePlateNumber,
        #[Argument(description: 'Latitude, in decimal degrees')] string $lat,
        #[Argument(description: 'Longitude, in decimal degrees')] string $lng,
        OutputInterface $output,
        #[Argument(description: 'Altitude, in meters')] ?string $alt = null,
    ): int {
        ($this->parkVehicle)(new ParkVehicle(
            $fleetId,
            $vehiclePlateNumber,
            self::toFloat('Latitude', $lat),
            self::toFloat('Longitude', $lng),
            $alt === null ? null : self::toFloat('Altitude', $alt),
        ));

        $output->writeln(sprintf('Vehicle %s localized.', $vehiclePlateNumber));

        return Command::SUCCESS;
    }

    private static function toFloat(string $name, string $value): float
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE)
            ?? throw new InvalidArgumentException(sprintf('%s must be a number, "%s" given.', $name, $value));
    }
}
