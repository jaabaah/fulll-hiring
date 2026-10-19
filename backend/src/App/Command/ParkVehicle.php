<?php

declare(strict_types=1);

namespace Fulll\App\Command;

final readonly class ParkVehicle
{
    public function __construct(
        public string $fleetId,
        public string $plateNumber,
        public float $latitude,
        public float $longitude,
        public ?float $altitude = null,
    ) {}
}
