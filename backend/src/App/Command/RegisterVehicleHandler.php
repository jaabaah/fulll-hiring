<?php

declare(strict_types=1);

namespace Fulll\App\Command;

use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\PlateNumber;

final readonly class RegisterVehicleHandler
{
    public function __construct(
        private FleetRepository $fleets,
    ) {
    }

    public function __invoke(RegisterVehicle $command): void
    {
        $fleet = $this->fleets->get(new FleetId($command->fleetId));

        $fleet->register(new PlateNumber($command->plateNumber));

        $this->fleets->save($fleet);
    }
}
