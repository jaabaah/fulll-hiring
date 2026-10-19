<?php

declare(strict_types=1);

namespace Fulll\Domain\Exception;

use Throwable;

/**
 * Every error raised on purpose by the fleet model: an invalid value, a refused business rule
 * or a concurrent modification. Entry points turn it into a readable error, anything else is a bug.
 */
interface FleetException extends Throwable {}
