<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Domain;

use Fulll\Domain\Exception\InvalidValue;
use Fulll\Domain\PlateNumber;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function itAcceptsUpToItsMaximumLengthInCharacters(): void
    {
        // Multibyte on purpose: the limit counts characters, like the VARCHAR column, not bytes.
        $longest = str_repeat('É', PlateNumber::MAX_LENGTH);

        self::assertSame($longest, new PlateNumber($longest)->value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'empty' => ['   '];
        yield 'too long' => [str_repeat('A', PlateNumber::MAX_LENGTH + 1)];
        yield 'control character' => ["AB-123\x00-CD"];
        yield 'invalid UTF-8' => ["AB-\xFF-CD"];
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function itRejectsAnInvalidValue(string $value): void
    {
        $this->expectException(InvalidValue::class);

        new PlateNumber($value);
    }
}
