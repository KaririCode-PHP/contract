<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute;

use Attribute;

/** Fixture: a repeatable validation rule. Example: `#[Validate('email')]` */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Validate implements Rule
{
    public function __construct(public string $rule) {}
}
