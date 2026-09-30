<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Environment\Fixture;

use KaririCode\Contract\Environment\EnvironmentResolver;
use Override;

/**
 * Minimal EnvironmentResolver over a fixed map: one snapshot, no globals.
 *
 * Example: `$environment = new ArrayEnvironmentResolver(['APP_ENV' => 'test']);`
 */
final readonly class ArrayEnvironmentResolver implements EnvironmentResolver
{
    /**
     * @param array<string, string> $variables
     */
    public function __construct(private array $variables) {}

    #[Override]
    public function get(string $name): ?string
    {
        return $this->variables[$name] ?? null;
    }

    #[Override]
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }
}
