<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Configuration;

use KaririCode\Contract\Tests\Unit\Configuration\Fixture\QueueConfigProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function theNamespaceIsOneTopLevelKey(): void
    {
        $namespace = (new QueueConfigProvider())->namespace();

        self::assertSame('queue', $namespace);
        self::assertStringNotContainsString('.', $namespace);
    }

    #[Test]
    public function keysAreRelativeToTheNamespace(): void
    {
        $provider = new QueueConfigProvider();
        $prefix = $provider->namespace() . '.';

        foreach ([...array_keys($provider->defaults()), ...array_keys($provider->schema())] as $key) {
            self::assertStringStartsNotWith($prefix, (string) $key);
        }
    }

    #[Test]
    public function everyDefaultIsDeclaredInTheSchemaWithItsType(): void
    {
        $provider = new QueueConfigProvider();
        $schema = $provider->schema();

        foreach ($provider->defaults() as $key => $value) {
            self::assertArrayHasKey($key, $schema);
            self::assertSame($schema[$key], get_debug_type($value), $key);
        }
    }
}
