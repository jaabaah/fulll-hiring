<?php

declare(strict_types=1);

namespace FizzBuzz\Tests;

use FizzBuzz\FizzBuzz;
use FizzBuzz\Rule;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FizzBuzzTest extends TestCase
{
    /** @return iterable<string, array{int, string}> */
    public static function classicCases(): iterable
    {
        yield 'not a multiple' => [1, '1'];
        yield 'multiple of 3' => [3, 'Fizz'];
        yield 'multiple of 5' => [5, 'Buzz'];
        yield 'multiple of 3 and 5' => [15, 'FizzBuzz'];
        yield 'another multiple of 3 and 5' => [30, 'FizzBuzz'];
    }

    #[Test]
    #[DataProvider('classicCases')]
    public function itConvertsANumber(int $number, string $expected): void
    {
        self::assertSame($expected, FizzBuzz::classic()->convert($number));
    }

    #[Test]
    public function itGeneratesTheSequenceFromOneToN(): void
    {
        $expected = [
            '1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8',
            'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz',
        ];

        self::assertSame($expected, iterator_to_array(FizzBuzz::classic()->upTo(15), false));
    }

    #[Test]
    public function itRejectsALimitLowerThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // The generator is lazy: the exception is thrown on the first iteration.
        FizzBuzz::classic()->upTo(0)->current();
    }

    #[Test]
    public function itSupportsNewRulesWithoutModification(): void
    {
        $fizzBuzzBazz = new FizzBuzz(new Rule(3, 'Fizz'), new Rule(5, 'Buzz'), new Rule(7, 'Bazz'));

        self::assertSame('Bazz', $fizzBuzzBazz->convert(7));
        self::assertSame('FizzBazz', $fizzBuzzBazz->convert(21));
        self::assertSame('FizzBuzzBazz', $fizzBuzzBazz->convert(105));
    }

    #[Test]
    public function itIsLazyAndDoesNotComputeTheWholeSequenceUpfront(): void
    {
        $sequence = FizzBuzz::classic()->upTo(PHP_INT_MAX);

        $firstThree = [];
        foreach ($sequence as $value) {
            $firstThree[] = $value;
            if (count($firstThree) === 3) {
                break;
            }
        }

        self::assertSame(['1', '2', 'Fizz'], $firstThree);
    }
}
