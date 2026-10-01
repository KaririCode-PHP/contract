<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Serialization\Fixture;

use KaririCode\Contract\SerializableData;

/** Fixture that breaks the precondition: it holds a pure enum. Example: `new Swatch(Shade::Dark)` */
final readonly class Swatch implements SerializableData
{
    public function __construct(public Shade $shade) {}
}
