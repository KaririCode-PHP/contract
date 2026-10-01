<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\DI\Fixture;

use KaririCode\Contract\DI\BindableContainer;
use Override;
use Throwable;

/**
 * Minimal BindableContainer: factories receive the container, class names
 * are built without arguments, and every get() is counted.
 *
 * Example: `$container->bind(Greeting::class, null, singleton: true);`
 */
final class ArrayContainer implements BindableContainer
{
    public int $lookups = 0;

    /** @var array<string, array{concrete: callable|string|null, singleton: bool}> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $values = [];

    #[Override]
    public function get(string $id): mixed
    {
        ++$this->lookups;

        if (array_key_exists($id, $this->values)) {
            return $this->values[$id];
        }

        $binding = $this->bindings[$id] ?? throw ServiceNotFound::for($id);

        if ($binding['singleton']) {
            return $this->values[$id] = $this->buildOrFail($id, $binding['concrete']);
        }

        return $this->buildOrFail($id, $binding['concrete']);
    }

    #[Override]
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->values) || array_key_exists($id, $this->bindings);
    }

    #[Override]
    public function bind(string $abstract, callable|string|null $concrete, bool $singleton = false): void
    {
        $this->bindings[$abstract] = ['concrete' => $concrete, 'singleton' => $singleton];
        unset($this->values[$abstract]);
    }

    #[Override]
    public function instance(string $abstract, mixed $value): void
    {
        $this->values[$abstract] = $value;
    }

    private function buildOrFail(string $id, callable|string|null $concrete): mixed
    {
        try {
            return $this->build($id, $concrete);
        } catch (ServiceNotFound $missing) {
            throw $missing;
        } catch (Throwable $error) {
            throw BuildFailed::for($id, $error);
        }
    }

    private function build(string $id, callable|string|null $concrete): mixed
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        $class = $concrete ?? $id;
        if (!class_exists($class)) {
            throw ServiceNotFound::for($class);
        }

        return (new \ReflectionClass($class))->newInstance();
    }
}
