<?php

declare(strict_types=1);

namespace Fulll\Tests\Assertion;

use Fulll\Domain\Location;
use PHPUnit\Framework\Assert;

trait LocationAssertions
{
    /**
     * Strict comparison through the domain (an altitude of 0 is not a missing altitude), null-safe.
     */
    private static function assertSameLocation(Location $expected, ?Location $actual): void
    {
        Assert::assertTrue(
            $actual !== null && $expected->equals($actual),
            sprintf('Expected location %s, got %s.', var_export($expected, true), var_export($actual, true)),
        );
    }
}
