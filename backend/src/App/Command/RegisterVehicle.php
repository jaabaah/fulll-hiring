<?php

declare(strict_types=1);

namespace Fulll\App\Command;

final readonly class RegisterVehicle
{
    public function __construct(
        public string $fleetId,
        public string $plateNumber,
    ) {
    }
}
