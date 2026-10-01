<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Serialization\Fixture;

/** Fixture: a backed enum crosses the wire by value (Spec V4.3.3 §10). Example: `Currency::BRL` */
enum Currency: string
{
    case BRL = 'BRL';
    case USD = 'USD';
}
