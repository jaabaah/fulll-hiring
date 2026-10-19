<?php

declare(strict_types=1);

namespace Fulll\Domain;

use Fulll\Domain\Exception\InvalidValue;

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
        // is_finite() first: NAN fails every comparison, so it would pass the bounds checks.
        if (!is_finite($latitude) || $latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidValue(sprintf('Latitude must be between -90 and 90, %s given.', $latitude));
        }

        if (!is_finite($longitude) || $longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidValue(sprintf('Longitude must be between -180 and 180, %s given.', $longitude));
        }

        if ($altitude !== null && !is_finite($altitude)) {
            throw new InvalidValue(sprintf('Altitude must be a finite number, %s given.', $altitude));
        }
    }

    public function equals(self $other): bool
    {
        return $this->latitude === $other->latitude
            && $this->longitude === $other->longitude
            && $this->altitude === $other->altitude;
    }
}
