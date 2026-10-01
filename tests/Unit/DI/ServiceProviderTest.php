<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\DI;

use KaririCode\Contract\DI\BindableContainer;
use KaririCode\Contract\Exception\ContainerFailure;
use KaririCode\Contract\Exception\NotFound;
use KaririCode\Contract\Tests\Unit\DI\Fixture\ArrayContainer;
use KaririCode\Contract\Tests\Unit\DI\Fixture\Greeting;
use KaririCode\Contract\Tests\Unit\DI\Fixture\RecordingProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ServiceProviderTest extends TestCase
{
    #[Test]
    public function everyProviderRegistersBeforeAnyBoots(): void
    {
        $container = new ArrayContainer();
        $needsGreeting = new RecordingProvider(static function (BindableContainer $c): void {}, [Greeting::class]);
        $bindsGreeting = new RecordingProvider(static function (BindableContainer $c): void {
            $c->bind(Greeting::class, static fn(BindableContainer $c): Greeting => new Greeting('hi'), singleton: true);
        });
        $providers = [$needsGreeting, $bindsGreeting];

        foreach ($providers as $provider) {
            $provider->register($container);
        }
        self::assertSame(0, $container->lookups, 'register() only binds');

        foreach ($providers as $provider) {
            $provider->boot();
        }

        $greeting = $needsGreeting->booted[Greeting::class] ?? null;
        self::assertInstanceOf(Greeting::class, $greeting, 'a provider registered later is visible at boot');
        self::assertSame('hi', $greeting->text);
    }

    #[Test]
    public function aSingletonIsBuiltOnceAndAPlainBindingEveryTime(): void
    {
        $container = new ArrayContainer();
        $container->bind('shared', static fn(): Greeting => new Greeting(), singleton: true);
        $container->bind('fresh', static fn(): Greeting => new Greeting());

        self::assertSame($container->get('shared'), $container->get('shared'));
        self::assertNotSame($container->get('fresh'), $container->get('fresh'));
    }

    #[Test]
    public function nullBuildsTheAbstractItselfAndInstanceBindsAValue(): void
    {
        $container = new ArrayContainer();
        $container->bind(Greeting::class, null);
        $container->instance('rate_limits.api', 600);

        self::assertInstanceOf(Greeting::class, $container->get(Greeting::class));
        self::assertSame(600, $container->get('rate_limits.api'));
        self::assertTrue($container->has('rate_limits.api'));
        self::assertFalse($container->has('missing'));
    }

    #[Test]
    public function anUnknownIdentifierIsNotFound(): void
    {
        $this->expectException(NotFound::class);

        (new ArrayContainer())->get('missing');
    }

    #[Test]
    public function aFactoryThatThrowsIsAContainerFailure(): void
    {
        $container = new ArrayContainer();
        $container->bind('broken', static fn(): never => throw new RuntimeException('no connection'));

        try {
            $container->get('broken');
            self::fail('A failing factory must surface.');
        } catch (ContainerFailure $failure) {
            self::assertInstanceOf(RuntimeException::class, $failure->getPrevious());
        }
    }
}
