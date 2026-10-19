<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Fulll\Tests\Behat\FleetContext;

return new Config()
    ->withProfile(
        new Profile('default')
            ->withSuite(
                new Suite('in_memory')
                    ->withPaths('%paths.base%/features')
                    ->withContexts(FleetContext::class),
            ),
    );
