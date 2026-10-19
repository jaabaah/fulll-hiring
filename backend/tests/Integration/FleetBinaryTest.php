<?php

declare(strict_types=1);

namespace Fulll\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Runs the real `./fleet` executable in a process: its composition root reads the configuration
 * and connects to the database lazily, which the in-process CLI tests cannot see.
 */
final class FleetBinaryTest extends TestCase
{
    #[Test]
    public function theCommandsCanBeListedWithoutADatabase(): void
    {
        [$exitCode, $output] = self::fleet([], ['list']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('localize-vehicle', $output);
    }

    #[Test]
    public function aMissingDatabaseUrlIsReportedAsAnError(): void
    {
        [$exitCode, , $errorOutput] = self::fleet([], ['create', 'john']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('DATABASE_URL is not set', $errorOutput);
    }

    #[Test]
    public function anInvalidDatabaseUrlIsReportedAsAnError(): void
    {
        [$exitCode, , $errorOutput] = self::fleet(['DATABASE_URL' => 'mysql://fleet@127.0.0.1/fleet'], ['create', 'john']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('must look like postgresql://', $errorOutput);
        self::assertStringNotContainsString('Fatal error', $errorOutput);
    }

    #[Test]
    public function anUnreachableDatabaseIsReportedAsAnError(): void
    {
        [$exitCode, , $errorOutput] = self::fleet(
            ['DATABASE_URL' => 'postgresql://fleet:fleet@127.0.0.1:1/fleet?connect_timeout=2'],
            ['create', 'john'],
        );

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Cannot connect to the database', $errorOutput);
    }

    /**
     * @param array<string, string> $environment the whole environment of the process
     * @param list<string>          $arguments
     *
     * @return array{int, string, string} exit code, output and error output
     */
    private static function fleet(array $environment, array $arguments): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__, 2) . '/fleet', ...$arguments],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $environment,
        );
        self::assertIsResource($process);

        $output = (string) stream_get_contents($pipes[1]);
        $errorOutput = (string) stream_get_contents($pipes[2]);

        return [proc_close($process), $output, $errorOutput];
    }
}
