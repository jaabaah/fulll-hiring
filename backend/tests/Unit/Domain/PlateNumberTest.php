<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Domain;

use Fulll\Domain\PlateNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlateNumberTest extends TestCase
{
    #[Test]
    public function itIsNormalizedToUppercaseWithoutSurroundingSpaces(): void
    {
        self::assertSame('AB-123-CD', new PlateNumber('  ab-123-cd ')->value);
    }

    #[Test]
    public function twoPlatesWrittenDifferentlyAreEqual(): void
    {
        self::assertTrue(new PlateNumber('ab-123-cd')->equals(new PlateNumber('AB-123-CD')));
        self::assertFalse(new PlateNumber('AB-123-CD')->equals(new PlateNumber('EF-456-GH')));
    }

    #[Test]
    public function itRejectsAnEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlateNumber('   ');
    }
}
