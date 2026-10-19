<?php

declare(strict_types=1);

namespace Fulll\App\Command;

use Fulll\Domain\Fleet;
use Fulll\Domain\FleetId;
use Fulll\Domain\FleetRepository;
use Fulll\Domain\UserId;

final readonly class CreateFleetHandler
{
    public function __construct(
        private FleetRepository $fleets,
    ) {}

    /**
     * Deliberate exception to strict CQRS: the generated id is returned,
     * since the caller has no other way to know it (e.g. `fleet create` must print it).
     */
    public function __invoke(CreateFleet $command): FleetId
    {
        $fleet = Fleet::create(FleetId::generate(), new UserId($command->userId));

        $this->fleets->save($fleet);

        return $fleet->id;
    }
}
