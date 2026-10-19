<?php

declare(strict_types=1);

namespace Fulll\Infra\InMemory;

use Fulll\Domain\Exception\FleetNotFound;
use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;

/**
 * Adapter for tests. Stores copies, so that it behaves like a real database:
 * a fleet modified but not saved is not persisted.
 * A shallow clone is enough since the vehicles inside a fleet are immutable.
 */
final class InMemoryFleetRepository implements FleetRepository
{
    /** @var array<string, Fleet> indexed by fleet id */
    private array $fleets = [];

    public function get(FleetId $id): Fleet
    {
        $fleet = $this->fleets[$id->value] ?? throw FleetNotFound::withId($id);

        return clone $fleet;
    }

    public function save(Fleet $fleet): void
    {
        $this->fleets[$fleet->id->value] = clone $fleet;
    }
}
