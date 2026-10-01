<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Serialization\Fixture;

use KaririCode\Contract\SerializableData;

/** Fixture holding another SerializableData. Example: `new Invoice('INV-1', new Money(1250, Currency::BRL))` */
final readonly class Invoice implements SerializableData
{
    public function __construct(public string $number, public Money $total) {}
}
