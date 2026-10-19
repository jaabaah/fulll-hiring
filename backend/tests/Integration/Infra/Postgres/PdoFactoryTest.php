<?php

declare(strict_types=1);

namespace Fulll\Tests\Integration\Infra\Postgres;

use Fulll\Infra\Postgres\PdoFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdoFactoryTest extends TestCase
{
    #[Test]
    public function itConnectsWithAValidUrl(): void
    {
        $statement = PdoFactory::fromUrl((string) getenv('TEST_DATABASE_URL'))->query('SELECT 1');

        self::assertNotFalse($statement);
        self::assertSame(1, $statement->fetchColumn());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidUrls(): iterable
    {
        yield 'another database engine' => ['mysql://fleet:fleet@127.0.0.1/fleet'];
        yield 'no database name' => ['postgresql://fleet:fleet@127.0.0.1'];
    }

    #[Test]
    #[DataProvider('invalidUrls')]
    public function itRejectsAnInvalidUrlWithoutConnecting(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        PdoFactory::fromUrl($url);
    }
}
