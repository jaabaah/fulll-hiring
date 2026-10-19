<?php

declare(strict_types=1);

namespace Fulll\App\Command;

use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\Location;
use Fulll\Domain\PlateNumber;

final readonly class ParkVehicleHandler
{
    public function __construct(
        private FleetRepository $fleets,
    ) {
    }

    public function __invoke(ParkVehicle $command): void
    {
        $fleet = $this->fleets->get(new FleetId($command->fleetId));

        $fleet->park(
            new PlateNumber($command->plateNumber),
            new Location($command->latitude, $command->longitude, $command->altitude),
        );

        $this->fleets->save($fleet);
    }
}
