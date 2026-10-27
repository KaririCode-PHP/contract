<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;

/** Fixture: an annotated static property beside an annotated instance one. Example: `new Defaults()` */
final class Defaults
{
    #[Sanitize('trim')]
    public static string $fallback = ' shared ';

    #[Sanitize('trim')]
    public string $title = ' own ';
}
