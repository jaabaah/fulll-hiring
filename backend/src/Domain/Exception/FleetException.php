<?php

declare(strict_types=1);

namespace Fulll\Domain\Exception;

use Throwable;

/**
 * Every error raised on purpose by the fleet model: an invalid value or a refused business rule.
 * Entry points turn it into a readable error, anything else is a bug.
 */
interface FleetException extends Throwable {}
