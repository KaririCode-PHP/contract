<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Context\Fixture;

use Fiber;
use InvalidArgumentException;
use KaririCode\Contract\Context\ContextStorage;
use Override;
use WeakMap;

/**
 * Minimal ContextStorage: one set of slots per fiber (a WeakMap, so a
 * finished fiber takes its context with it) and one for the main flow.
 *
 * Example: `$storage->store(Identity::class, $identity);`
 */
final class FiberContextStorage implements ContextStorage
{
    /** @var WeakMap<Fiber, array<string, object>> */
    private WeakMap $fibers;

    /** @var array<string, object> */
    private array $main = [];

    public function __construct()
    {
        /** @var WeakMap<Fiber, array<string, object>> $fibers */
        $fibers = new WeakMap();
        $this->fibers = $fibers;
    }

    /**
     * PHP cannot enforce the template, so the precondition is checked here.
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    #[Override]
    public function store(string $type, object $value): void
    {
        if (!$value instanceof $type) {
            throw new InvalidArgumentException(sprintf('%s is not a %s.', get_debug_type($value), $type));
        }

        $slots = $this->slots();
        $slots[$type] = $value;
        $this->write($slots);
    }

    #[Override]
    public function find(string $type): ?object
    {
        $value = $this->slots()[$type] ?? null;

        return $value instanceof $type ? $value : null;
    }

    #[Override]
    public function clear(): void
    {
        $this->write([]);
    }

    /** @return array<string, object> */
    private function slots(): array
    {
        $fiber = Fiber::getCurrent();
        if ($fiber === null) {
            return $this->main;
        }

        $slots = $this->fibers[$fiber] ?? [];

        return is_array($slots) ? $slots : [];
    }

    /** @param array<string, object> $slots */
    private function write(array $slots): void
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null) {
            $this->main = $slots;
        } else {
            $this->fibers[$fiber] = $slots;
        }
    }
}
