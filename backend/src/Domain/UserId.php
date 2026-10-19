<?php

declare(strict_types=1);

namespace Fulll\Domain;

use Fulll\Domain\Exception\InvalidValue;

final readonly class UserId
{
    /** In characters. The database column is sized from it (database/schema.sql). */
    public const int MAX_LENGTH = 255;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidValue('User id must not be empty.');
        }

        // Valid UTF-8 (the "u" modifier fails otherwise), without control characters.
        if (preg_match(sprintf('/^\P{C}{1,%d}$/u', self::MAX_LENGTH), $value) !== 1) {
            throw new InvalidValue(sprintf('User id must be at most %d printable characters.', self::MAX_LENGTH));
        }

        $this->value = $value;
    }
}
