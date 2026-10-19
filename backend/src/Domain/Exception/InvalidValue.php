<?php

declare(strict_types=1);

namespace Fulll\Domain\Exception;

use InvalidArgumentException;

/**
 * A value object refused the data it was built from.
 */
final class InvalidValue extends InvalidArgumentException implements FleetException {}
