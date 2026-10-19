<?php

declare(strict_types=1);

namespace Fulll\Infra\Postgres;

use InvalidArgumentException;
use PDO;
use SensitiveParameter;

final class PdoFactory
{
    /**
     * Builds a PDO connection from a URL such as postgresql://user:password@host:5432/database.
     */
    public static function fromUrl(#[SensitiveParameter] string $url): PDO
    {
        $parts = parse_url($url);

        if ($parts === false || !in_array($parts['scheme'] ?? null, ['postgres', 'postgresql'], true) || !isset($parts['host'], $parts['path'])) {
            throw new InvalidArgumentException('DATABASE_URL must look like postgresql://user:password@host:5432/database.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $parts['host'],
            $parts['port'] ?? 5432,
            rawurldecode(ltrim($parts['path'], '/')),
        );

        // rawurldecode(), not urldecode(): a "+" in a password is a plus sign, not a space.
        return new PDO($dsn, rawurldecode($parts['user'] ?? ''), rawurldecode($parts['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
