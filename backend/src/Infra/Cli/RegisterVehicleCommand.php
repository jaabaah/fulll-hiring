<?php

declare(strict_types=1);

namespace Fulll\Infra\Cli;

use Fulll\App\Command\RegisterVehicle;
use Fulll\App\Command\RegisterVehicleHandler;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'register-vehicle', description: 'Registers a vehicle into a fleet')]
final readonly class RegisterVehicleCommand
{
    public function __construct(
        private RegisterVehicleHandler $registerVehicle,
    ) {
    }

    public function __invoke(
        #[Argument(description: 'Id of the fleet')] string $fleetId,
        #[Argument(description: 'Plate number of the vehicle')] string $vehiclePlateNumber,
        OutputInterface $output,
    ): int {
        ($this->registerVehicle)(new RegisterVehicle($fleetId, $vehiclePlateNumber));

        $output->writeln(sprintf('Vehicle %s registered into fleet %s.', $vehiclePlateNumber, $fleetId));

        return Command::SUCCESS;
    }
}
