<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\DI\Fixture;

use Closure;
use KaririCode\Contract\DI\BindableContainer;
use KaririCode\Contract\DI\ServiceProvider;
use Override;

/**
 * Fixture provider: register() runs the given bindings, boot() resolves
 * the given identifiers from the container it received.
 *
 * Example: `new RecordingProvider(static fn (BindableContainer $c) => $c->instance('a', 1), ['a'])`
 */
final class RecordingProvider implements ServiceProvider
{
    /** @var array<string, mixed> */
    public array $booted = [];

    private ?BindableContainer $container = null;

    /**
     * @param Closure(BindableContainer): void $bindings
     * @param list<string>                     $resolveOnBoot
     */
    public function __construct(private readonly Closure $bindings, private readonly array $resolveOnBoot = []) {}

    #[Override]
    public function register(BindableContainer $container): void
    {
        $this->container = $container;
        ($this->bindings)($container);
    }

    #[Override]
    public function boot(): void
    {
        foreach ($this->resolveOnBoot as $id) {
            $this->booted[$id] = $this->container?->get($id);
        }
    }
}
