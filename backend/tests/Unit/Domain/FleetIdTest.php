<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Domain;

use Fulll\Domain\FleetId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FleetIdTest extends TestCase
{
    #[Test]
    public function itGeneratesUniqueUuids(): void
    {
        $first = FleetId::generate();
        $second = FleetId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $first->value,
        );
        self::assertFalse($first->equals($second));
    }

    #[Test]
    public function itCanBeRebuiltFromItsStringValue(): void
    {
        $id = FleetId::generate();

        self::assertTrue(new FleetId($id->value)->equals($id));
    }

    #[Test]
    public function itRejectsAValueThatIsNotAUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FleetId('not-a-uuid');
    }
}
