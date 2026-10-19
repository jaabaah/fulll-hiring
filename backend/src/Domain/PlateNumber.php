<?php

declare(strict_types=1);

namespace Fulll\Domain;

use InvalidArgumentException;

/**
 * Identity of a vehicle. Normalized so that "ab-123-cd" and "AB-123-CD" are the same vehicle.
 */
final readonly class PlateNumber
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            throw new InvalidArgumentException('Plate number must not be empty.');
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
