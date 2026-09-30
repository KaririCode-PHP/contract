<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Environment;

use KaririCode\Contract\Tests\Unit\Environment\Fixture\ArrayEnvironmentResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvironmentResolverTest extends TestCase
{
    #[Test]
    public function aDefinedVariableResolvesToItsValue(): void
    {
        $environment = new ArrayEnvironmentResolver(['DATABASE_URL' => 'pgsql://db/app']);

        self::assertSame('pgsql://db/app', $environment->get('DATABASE_URL'));
        self::assertTrue($environment->has('DATABASE_URL'));
    }

    #[Test]
    public function anUndefinedVariableResolvesToNull(): void
    {
        $environment = new ArrayEnvironmentResolver([]);

        self::assertNull($environment->get('DATABASE_URL'));
        self::assertFalse($environment->has('DATABASE_URL'));
    }

    #[Test]
    public function hasIsTrueExactlyWhenGetIsNotNull(): void
    {
        $environment = new ArrayEnvironmentResolver(['EMPTY' => '', 'SET' => 'value']);

        foreach (['EMPTY', 'SET', 'MISSING'] as $name) {
            self::assertSame($environment->get($name) !== null, $environment->has($name), $name);
        }

        self::assertSame('', $environment->get('EMPTY'));
    }

    #[Test]
    public function itAnswersFromItsSnapshotNotFromProcessGlobals(): void
    {
        $name = 'KARIRICODE_CONTRACT_' . bin2hex(random_bytes(4));
        $environment = new ArrayEnvironmentResolver([]);
        $_ENV[$name] = 'global';
        putenv($name . '=global');

        try {
            self::assertNull($environment->get($name));
            self::assertFalse($environment->has($name));
        } finally {
            unset($_ENV[$name]);
            putenv($name);
        }
    }
}
