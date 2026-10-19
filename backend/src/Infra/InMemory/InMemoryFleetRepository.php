<?php

declare(strict_types=1);

namespace Fulll\Infra\InMemory;

use Fulll\Domain\Exception\FleetModifiedConcurrently;
use Fulll\Domain\Exception\FleetNotFound;
use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use WeakMap;

/**
 * Adapter for tests. Stores copies, so that it behaves like a real database:
 * a fleet modified but not saved is not persisted.
 * A shallow clone is enough since the vehicles inside a fleet are immutable.
 */
final class InMemoryFleetRepository implements FleetRepository
{
    /** @var array<string, array{fleet: Fleet, version: int}> indexed by fleet id */
    private array $fleets = [];

    /** @var WeakMap<Fleet, int> version of every fleet handed out by get() or saved */
    private WeakMap $versions;

    public function __construct()
    {
        $this->versions = new WeakMap();
    }

    public function get(FleetId $id): Fleet
    {
        $stored = $this->fleets[$id->value] ?? throw FleetNotFound::withId($id);
        $fleet = clone $stored['fleet'];
        $this->versions[$fleet] = $stored['version'];

        return $fleet;
    }

    public function save(Fleet $fleet): void
    {
        $storedVersion = $this->fleets[$fleet->id->value]['version'] ?? null;

        if ($storedVersion !== ($this->versions[$fleet] ?? null)) {
            throw FleetModifiedConcurrently::withId($fleet->id);
        }

        $version = $storedVersion === null ? 0 : $storedVersion + 1;
        $this->fleets[$fleet->id->value] = ['fleet' => clone $fleet, 'version' => $version];
        $this->versions[$fleet] = $version;
    }
}
