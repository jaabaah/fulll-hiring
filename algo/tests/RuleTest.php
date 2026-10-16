<?php

declare(strict_types=1);

namespace FizzBuzz\Tests;

use FizzBuzz\Rule;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase
{
    #[Test]
    public function itAppliesToMultiplesOfItsDivisor(): void
    {
        $rule = new Rule(3, 'Fizz');

        self::assertTrue($rule->appliesTo(3));
        self::assertTrue($rule->appliesTo(9));
        self::assertFalse($rule->appliesTo(4));
    }

    #[Test]
    public function itRejectsADivisorLowerThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule(0, 'Fizz');
    }

    #[Test]
    public function itRejectsAnEmptyWord(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule(3, '');
    }
}
