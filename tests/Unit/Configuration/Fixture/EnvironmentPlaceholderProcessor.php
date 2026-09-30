<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Configuration\Fixture;

use KaririCode\Contract\Configuration\ValueProcessor;
use KaririCode\Contract\Environment\EnvironmentResolver;
use Override;

/**
 * Minimal ValueProcessor that replaces ${NAME} with a value read through
 * an EnvironmentResolver, as Configurator's interpolation does.
 *
 * Example: `(new EnvironmentPlaceholderProcessor($environment))->process('${PORT}');`
 */
final readonly class EnvironmentPlaceholderProcessor implements ValueProcessor
{
    public function __construct(private EnvironmentResolver $environment) {}

    #[Override]
    public function supports(mixed $value): bool
    {
        return is_string($value) && str_contains($value, '${');
    }

    #[Override]
    public function process(mixed $value): mixed
    {
        return preg_replace_callback(
            '/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            fn(array $match): string => $this->environment->get($match[1]) ?? '',
            (string) $value,
        );
    }
}
