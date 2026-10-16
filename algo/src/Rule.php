<?php

declare(strict_types=1);

namespace FizzBuzz;

use InvalidArgumentException;

/**
 * Replaces multiples of a divisor by a word (e.g. 3 => "Fizz").
 */
final readonly class Rule
{
    public function __construct(
        public int $divisor,
        public string $word,
    ) {
        if ($divisor < 1) {
            throw new InvalidArgumentException(sprintf('Divisor must be at least 1, %d given.', $divisor));
        }

        if ($word === '') {
            throw new InvalidArgumentException('Word must not be empty.');
        }
    }

    public function appliesTo(int $number): bool
    {
        return $number % $this->divisor === 0;
    }
}
