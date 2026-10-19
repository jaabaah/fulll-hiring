<?php

declare(strict_types=1);

namespace Fulll\Domain\Exception;

use Fulll\Domain\FleetId;
use RuntimeException;

/**
 * Optimistic locking: the fleet was saved by someone else after it had been loaded.
 * Nothing was written, the whole operation can be retried.
 */
final class FleetModifiedConcurrently extends RuntimeException implements FleetException
{
    public static function withId(FleetId $id): self
    {
        return new self(sprintf('Fleet "%s" has been modified in the meantime, please try again.', $id->value));
    }
}
