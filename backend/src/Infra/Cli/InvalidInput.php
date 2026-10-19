<?php

declare(strict_types=1);

namespace Fulll\Infra\Cli;

use InvalidArgumentException;

/**
 * A command line argument that cannot even be turned into a command (e.g. a coordinate that is not a number).
 */
final class InvalidInput extends InvalidArgumentException
{
    public static function notANumber(string $name, string $value): self
    {
        return new self(sprintf('%s must be a number, "%s" given.', $name, $value));
    }
}
