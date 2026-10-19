<?php

declare(strict_types=1);

namespace Fulll\App\Query;

use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\Location;
use Fulll\Domain\PlateNumber;

final readonly class GetVehicleLocationHandler
{
    public function __construct(
        private FleetRepository $fleets,
    ) {
    }

    /**
     * @return Location|null null if the vehicle has never been parked
     */
    public function __invoke(GetVehicleLocation $query): ?Location
    {
        return $this->fleets
            ->get(new FleetId($query->fleetId))
            ->locationOf(new PlateNumber($query->plateNumber));
    }
}
