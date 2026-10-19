<?php

declare(strict_types=1);

namespace Fulll\Domain\Exception;

use DomainException;
use Fulll\Domain\FleetId;

final class FleetNotFound extends DomainException
{
    public static function withId(FleetId $id): self
    {
        return new self(sprintf('Fleet "%s" not found.', $id->value));
    }
}
