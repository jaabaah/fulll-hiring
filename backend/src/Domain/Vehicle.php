<?php

declare(strict_types=1);

namespace Fulll\Domain;

use Fulll\Domain\Exception\VehicleAlreadyParkedHere;

/**
 * Entity internal to the Fleet aggregate, identified by its plate number.
 * Immutable: parking returns a new state, which the Fleet keeps.
 */
final readonly class Vehicle
{
    public function __construct(
        public PlateNumber $plateNumber,
        public ?Location $location = null,
    ) {
    }

    /**
     * @throws VehicleAlreadyParkedHere
     */
    public function parkAt(Location $location): self
    {
        if ($this->location?->equals($location)) {
            throw VehicleAlreadyParkedHere::withPlate($this->plateNumber);
        }

        return new self($this->plateNumber, $location);
    }
}
