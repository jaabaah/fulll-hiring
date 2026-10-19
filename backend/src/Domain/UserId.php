<?php

declare(strict_types=1);

namespace Fulll\Domain;

use InvalidArgumentException;

final readonly class UserId
{
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('User id must not be empty.');
        }

        $this->value = $value;
    }
}
