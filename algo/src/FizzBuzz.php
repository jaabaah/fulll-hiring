<?php

declare(strict_types=1);

namespace FizzBuzz;

use Generator;
use InvalidArgumentException;

final readonly class FizzBuzz
{
    /** @var list<Rule> */
    private array $rules;

    public function __construct(Rule ...$rules)
    {
        $this->rules = array_values($rules);
    }

    public static function classic(): self
    {
        return new self(new Rule(3, 'Fizz'), new Rule(5, 'Buzz'));
    }

    /**
     * Lazily yields the conversion of every number from 1 to $limit:
     * O(n) time, O(1) memory. An invalid limit is refused right away, not on the first iteration.
     *
     * @return Generator<int, string>
     */
    public function upTo(int $limit): Generator
    {
        if ($limit < 1) {
            throw new InvalidArgumentException(sprintf('Limit must be at least 1, %d given.', $limit));
        }

        return $this->sequence($limit);
    }

    /**
     * @return Generator<int, string>
     */
    private function sequence(int $limit): Generator
    {
        for ($number = 1; $number <= $limit; ++$number) {
            yield $number => $this->convert($number);
        }
    }

    /**
     * Concatenates the words of every matching rule, in order,
     * so "FizzBuzz" emerges from the 3 and 5 rules without a special case.
     */
    public function convert(int $number): string
    {
        $words = '';
        foreach ($this->rules as $rule) {
            if ($rule->appliesTo($number)) {
                $words .= $rule->word;
            }
        }

        return $words === '' ? (string) $number : $words;
    }
}
