<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Broken;

/** Fixture: a property whose attribute cannot be instantiated. Example: `new BrokenDeclaration()` */
final class BrokenDeclaration
{
    #[Broken]
    public string $title = 'x';
}
