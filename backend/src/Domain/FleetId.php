<?php

declare(strict_types=1);

namespace Fulll\Domain;

use InvalidArgumentException;

final readonly class FleetId
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public function __construct(public string $value)
    {
        if (preg_match(self::UUID_PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid fleet id.', $value));
        }
    }

    /**
     * Generates a random UUID v4 (RFC 9562) with native PHP only, no dependency needed.
     */
    public static function generate(): self
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // version 4
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // RFC variant

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
