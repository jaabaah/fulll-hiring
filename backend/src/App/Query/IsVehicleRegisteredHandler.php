<?php

declare(strict_types=1);

namespace Fulll\App\Query;

use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\PlateNumber;

final readonly class IsVehicleRegisteredHandler
{
    public function __construct(
        private FleetRepository $fleets,
    ) {}

    public function __invoke(IsVehicleRegistered $query): bool
    {
        return $this->fleets
            ->get(new FleetId($query->fleetId))
            ->hasVehicle(new PlateNumber($query->plateNumber));
    }
}
