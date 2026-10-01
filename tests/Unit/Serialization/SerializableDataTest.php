<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Serialization;

use JsonException;
use KaririCode\Contract\SerializableData;
use KaririCode\Contract\Tests\Unit\Serialization\Fixture\Currency;
use KaririCode\Contract\Tests\Unit\Serialization\Fixture\Invoice;
use KaririCode\Contract\Tests\Unit\Serialization\Fixture\Money;
use KaririCode\Contract\Tests\Unit\Serialization\Fixture\Shade;
use KaririCode\Contract\Tests\Unit\Serialization\Fixture\Swatch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SerializableDataTest extends TestCase
{
    #[Test]
    public function thePublicReadonlyPropertiesAreTheSerializedForm(): void
    {
        self::assertSame('{"amount":1250,"currency":"BRL"}', json_encode(new Money(1250, Currency::BRL), JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function serializableDataNests(): void
    {
        self::assertSame(
            '{"number":"INV-1","total":{"amount":1250,"currency":"BRL"}}',
            json_encode(new Invoice('INV-1', new Money(1250, Currency::BRL)), JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function aPureEnumBreaksThePrecondition(): void
    {
        $this->expectException(JsonException::class);

        json_encode(new Swatch(Shade::Dark), JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function theMarkerDeclaresNothing(): void
    {
        $marker = new ReflectionClass(SerializableData::class);

        self::assertTrue($marker->isInterface());
        self::assertSame([], $marker->getMethods());
        self::assertSame([], $marker->getProperties());
    }
}
