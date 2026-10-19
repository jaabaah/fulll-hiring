<?php

declare(strict_types=1);

namespace Fulll\Tests\Unit\Domain;

use Fulll\Domain\UserId;
use InvalidArgumentException;
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
    public function itRejectsAnEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserId('');
    }
}
