<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Configuration\Fixture;

use KaririCode\Contract\Configuration\ValueProcessor;
use Override;
use RuntimeException;

/**
 * Minimal ValueProcessor: turns "int:8080" into 8080.
 *
 * Example: `(new IntegerCastProcessor())->process('int:8080'); // 8080`
 */
final readonly class IntegerCastProcessor implements ValueProcessor
{
    private const string PREFIX = 'int:';

    #[Override]
    public function supports(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    #[Override]
    public function process(mixed $value): mixed
    {
        $digits = substr((string) $value, strlen(self::PREFIX));

        if (preg_match('/^-?\d+\z/', $digits) !== 1) {
            throw new RuntimeException(sprintf('Not an integer: "%s".', $digits));
        }

        return (int) $digits;
    }
}
