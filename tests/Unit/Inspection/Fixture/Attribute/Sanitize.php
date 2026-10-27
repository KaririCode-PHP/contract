<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute;

use Attribute;

/** Fixture: a repeatable sanitizing rule. Example: `#[Sanitize('trim')]` */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Sanitize implements Rule
{
    public function __construct(public string $rule) {}
}
