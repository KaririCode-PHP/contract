<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Serialization\Fixture;

/** Fixture: a pure enum has no serialized form. Example: `Shade::Dark` */
enum Shade
{
    case Light;
    case Dark;
}
