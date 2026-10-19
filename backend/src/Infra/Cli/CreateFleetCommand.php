<?php

declare(strict_types=1);

namespace Fulll\Infra\Cli;

use Fulll\App\Command\CreateFleet;
use Fulll\App\Command\CreateFleetHandler;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'create', description: 'Creates a fleet for a user and prints its id')]
final readonly class CreateFleetCommand
{
    public function __construct(
        private CreateFleetHandler $createFleet,
    ) {
    }

    public function __invoke(
        #[Argument(description: 'Id of the user owning the fleet')] string $userId,
        OutputInterface $output,
    ): int {
        // Only the id on stdout, so that it can be captured: FLEET_ID=$(./fleet create john)
        $output->writeln(($this->createFleet)(new CreateFleet($userId))->value);

        return Command::SUCCESS;
    }
}
