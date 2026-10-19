<?php

declare(strict_types=1);

namespace Fulll\Domain\Exception;

use DomainException;
use Fulll\Domain\PlateNumber;

final class VehicleNotRegistered extends DomainException
{
    public static function withPlate(PlateNumber $plate): self
    {
        return new self(sprintf('Vehicle "%s" is not registered into this fleet.', $plate->value));
    }
}
