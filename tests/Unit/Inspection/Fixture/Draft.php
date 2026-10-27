<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;

/** Fixture: a mutable DTO whose value changes between inspections. Example: `$draft->title = 'second';` */
final class Draft
{
    #[Sanitize('trim')]
    public string $title = 'first';

    public string $unannotated = 'never handed to a handler';
}
