<?php

declare(strict_types=1);

namespace Fulll\Domain;

use InvalidArgumentException;

/**
 * GPS coordinates in decimal degrees, with an optional altitude in meters.
 */
final readonly class Location
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?float $altitude = null,
    ) {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException(sprintf('Latitude must be between -90 and 90, %F given.', $latitude));
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException(sprintf('Longitude must be between -180 and 180, %F given.', $longitude));
        }
    }

    public function equals(self $other): bool
    {
        return $this->latitude === $other->latitude
            && $this->longitude === $other->longitude
            && $this->altitude === $other->altitude;
    }
}
