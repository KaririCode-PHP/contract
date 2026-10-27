<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute;

use Attribute;
use LogicException;

/** Fixture: an attribute whose constructor rejects its declaration. Example: `#[Broken]` */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Broken
{
    public function __construct()
    {
        throw new LogicException('This rule is declared wrong.');
    }
}
