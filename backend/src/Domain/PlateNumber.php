<?php

declare(strict_types=1);

namespace Fulll\Domain;

use Fulll\Domain\Exception\InvalidValue;

/**
 * Identity of a vehicle. Normalized so that "ab-123-cd" and "AB-123-CD" are the same vehicle.
 */
final readonly class PlateNumber
{
    /** In characters. The database column is sized from it (database/schema.sql). */
    public const int MAX_LENGTH = 32;

    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            throw new InvalidValue('Plate number must not be empty.');
        }

        // Valid UTF-8 (the "u" modifier fails otherwise), without control characters.
        if (preg_match(sprintf('/^\P{C}{1,%d}$/u', self::MAX_LENGTH), $normalized) !== 1) {
            throw new InvalidValue(sprintf('Plate number must be at most %d printable characters.', self::MAX_LENGTH));
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
