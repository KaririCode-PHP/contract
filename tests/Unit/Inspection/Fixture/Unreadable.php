<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Exception\Fixture\InvalidEmail;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;

/**
 * Fixture: an annotated virtual property whose get hook throws a
 * Validation-family exception, which the inspector must let through.
 * Example: `new Unreadable()`
 */
final class Unreadable
{
    #[Sanitize('trim')]
    public string $email {
        get => throw new InvalidEmail('The stored e-mail address is corrupt.');
    }
}
