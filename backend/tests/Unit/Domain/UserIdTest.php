<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Domain;

use Fulll\Domain\Exception\InvalidValue;
use Fulll\Domain\UserId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    #[Test]
    public function itKeepsItsValueWithoutSurroundingSpaces(): void
    {
        self::assertSame('john', new UserId(' john ')->value);
    }

    #[Test]
    public function itAcceptsUpToItsMaximumLengthInCharacters(): void
    {
        $longest = str_repeat('é', UserId::MAX_LENGTH);

        self::assertSame($longest, new UserId($longest)->value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'too long' => [str_repeat('a', UserId::MAX_LENGTH + 1)];
        yield 'control character' => ["jo\nhn"];
        yield 'invalid UTF-8' => ["jo\xFFhn"];
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function itRejectsAnInvalidValue(string $value): void
    {
        $this->expectException(InvalidValue::class);

        new UserId($value);
    }
}
