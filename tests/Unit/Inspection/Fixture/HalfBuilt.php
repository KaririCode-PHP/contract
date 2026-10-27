<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;

/**
 * Fixture: an annotated typed property that was never initialized. Example: `new HalfBuilt()`
 *
 * @psalm-suppress MissingConstructor the uninitialized property is what this fixture is for
 */
final class HalfBuilt
{
    #[Sanitize('trim')]
    public string $title;
}
