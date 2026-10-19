<?php

declare(strict_types=1);

namespace Fulll\App\Command;

final readonly class CreateFleet
{
    public function __construct(
        public string $userId,
    ) {}
}
