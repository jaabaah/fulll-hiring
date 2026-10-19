<?php

declare(strict_types=1);

namespace Fulll\Domain;

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

    public function save(Fleet $fleet): void;
}
