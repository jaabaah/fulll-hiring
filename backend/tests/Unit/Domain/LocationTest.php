<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Domain;

use Fulll\Domain\Location;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    #[Test]
    public function itAcceptsTheBoundariesOfTheEarth(): void
    {
        $location = new Location(-90.0, 180.0);

        self::assertSame(-90.0, $location->latitude);
        self::assertSame(180.0, $location->longitude);
        self::assertNull($location->altitude);
    }

    /** @return iterable<string, array{float, float}> */
    public static function outOfRangeCoordinates(): iterable
    {
        yield 'latitude too low' => [-90.1, 0.0];
        yield 'latitude too high' => [90.1, 0.0];
        yield 'longitude too low' => [0.0, -180.1];
        yield 'longitude too high' => [0.0, 180.1];
    }

    #[Test]
    #[DataProvider('outOfRangeCoordinates')]
    public function itRejectsCoordinatesOutsideTheEarth(float $latitude, float $longitude): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Location($latitude, $longitude);
    }

    #[Test]
    public function twoLocationsWithTheSameCoordinatesAreEqual(): void
    {
        self::assertTrue(new Location(43.5283, 5.4497, 173.0)->equals(new Location(43.5283, 5.4497, 173.0)));
    }

    #[Test]
    public function aDifferentAltitudeMakesADifferentLocation(): void
    {
        // e.g. two levels of the same car park
        self::assertFalse(new Location(43.5283, 5.4497, 10.0)->equals(new Location(43.5283, 5.4497, 20.0)));
        self::assertFalse(new Location(43.5283, 5.4497)->equals(new Location(43.5283, 5.4497, 0.0)));
    }
}
