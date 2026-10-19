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
    public function itPassesTheQueryParametersAsConnectionOptions(): void
    {
        $url = (string) getenv('TEST_DATABASE_URL');
        $url .= (str_contains($url, '?') ? '&' : '?') . 'application_name=fleet_tests';

        $statement = PdoFactory::fromUrl($url)->query("SELECT current_setting('application_name')");

        self::assertNotFalse($statement);
        self::assertSame('fleet_tests', $statement->fetchColumn());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidUrls(): iterable
    {
        yield 'another database engine' => ['mysql://fleet:fleet@127.0.0.1/fleet'];
        yield 'no database name' => ['postgresql://fleet:fleet@127.0.0.1'];
        yield 'option injecting another option' => ['postgresql://fleet:fleet@127.0.0.1/fleet?sslmode=disable;host=elsewhere'];
        yield 'option with a space' => ['postgresql://fleet:fleet@127.0.0.1/fleet?application_name=my%20app'];
        yield 'option with an invalid name' => ['postgresql://fleet:fleet@127.0.0.1/fleet?ssl-mode=require'];
    }

    #[Test]
    #[DataProvider('invalidUrls')]
    public function itRejectsAnInvalidUrlWithoutConnecting(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        PdoFactory::fromUrl($url);
    }
}
