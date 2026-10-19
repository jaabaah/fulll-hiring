<?php

declare(strict_types=1);

namespace Fulll\Infra\Cli;

use Fulll\App\Command\CreateFleetHandler;
use Fulll\App\Command\ParkVehicleHandler;
use Fulll\App\Command\RegisterVehicleHandler;
use Fulll\Domain\Exception\FleetException;
use Fulll\Domain\FleetRepository;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The `fleet` CLI. Its factory is the composition root: the only place wiring handlers to a repository.
 */
final class FleetApplication extends Application
{
    public static function create(FleetRepository $fleets): self
    {
        $application = new self('fleet');
        $application->addCommand(new CreateFleetCommand(new CreateFleetHandler($fleets)));
        $application->addCommand(new RegisterVehicleCommand(new RegisterVehicleHandler($fleets)));
        $application->addCommand(new LocalizeVehicleCommand(new ParkVehicleHandler($fleets)));

        return $application;
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        return parent::run($input ?? new CoordinatesArgvInput(), $output);
    }

    /**
     * Turns the errors raised on purpose (refused business rules, invalid inputs) into a readable error
     * and a failure exit code, in one place for every command. Unexpected errors keep the default handling.
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::doRunCommand($command, $input, $output);
        } catch (FleetException|InvalidInput $error) {
            new SymfonyStyle($input, $output)->getErrorStyle()->error($error->getMessage());

            return Command::FAILURE;
        }
    }
}
