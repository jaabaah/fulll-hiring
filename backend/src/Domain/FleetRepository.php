<?php

declare(strict_types=1);

namespace Fulll\Domain;

use Fulll\Domain\Exception\FleetModifiedConcurrently;
use Fulll\Domain\Exception\FleetNotFound;

/**
 * Port: the domain defines the contract, the infrastructure provides the implementations.
 */
interface FleetRepository
{
    /**
     * @throws FleetNotFound
     */
    public function get(FleetId $id): Fleet;

    /**
     * Optimistic locking: a fleet can only be saved if nobody saved it since it was loaded
     * (or, for a new fleet, if no fleet has this id yet). Otherwise nothing is written.
     *
     * @throws FleetModifiedConcurrently
     */
    public function save(Fleet $fleet): void;
}
