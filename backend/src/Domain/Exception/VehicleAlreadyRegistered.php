<?php

declare(strict_types=1);

namespace Fulll\Domain\Exception;

use DomainException;
use Fulll\Domain\PlateNumber;

final class VehicleAlreadyRegistered extends DomainException implements FleetException
{
    public static function withPlate(PlateNumber $plate): self
    {
        return new self(sprintf('Vehicle "%s" has already been registered into this fleet.', $plate->value));
    }
}
