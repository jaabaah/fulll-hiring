<?php

declare(strict_types=1);

namespace Fulll\Infra\Cli;

use Symfony\Component\Console\Input\ArgvInput;

/**
 * By default, "-33.8688" would be read as the "-3" short option.
 * No option of this CLI is a number, so a numeric token is always an argument (e.g. a negative coordinate).
 */
final class CoordinatesArgvInput extends ArgvInput
{
    protected function parseToken(string $token, bool $parseOptions): bool
    {
        if (!is_numeric($token)) {
            return parent::parseToken($token, $parseOptions);
        }

        parent::parseToken($token, false);

        return $parseOptions;
    }
}
