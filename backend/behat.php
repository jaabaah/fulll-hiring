<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Fulll\Tests\Behat\FleetContext;

// Dedicated variable: scenarios empty their database, they must never use the application DATABASE_URL.
$testDatabaseUrl = getenv('TEST_DATABASE_URL') ?: 'postgresql://fleet:fleet@127.0.0.1:5442/fleet_test';

return new Config()
    ->withProfile(
        new Profile('default')
            // Every scenario, fast, without any infrastructure.
            ->withSuite(
                new Suite('in_memory')
                    ->withPaths('%paths.base%/features')
                    ->addContext(FleetContext::class),
            )
            // Critical scenarios only, against the real database (docker compose up -d).
            ->withSuite(
                new Suite('postgres')
                    ->withPaths('%paths.base%/features')
                    ->withFilter(new TagFilter('@critical'))
                    ->addContext(FleetContext::class, ['databaseUrl' => $testDatabaseUrl]),
            ),
    );
