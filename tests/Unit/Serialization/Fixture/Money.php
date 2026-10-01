<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Serialization\Fixture;

use KaririCode\Contract\SerializableData;

/** Fixture value object. Example: `new Money(1250, Currency::BRL)` */
final readonly class Money implements SerializableData
{
    public function __construct(public int $amount, public Currency $currency) {}
}
