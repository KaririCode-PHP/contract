<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Configuration;

use KaririCode\Contract\Configuration\ValueProcessor;
use KaririCode\Contract\Tests\Unit\Configuration\Fixture\EnvironmentPlaceholderProcessor;
use KaririCode\Contract\Tests\Unit\Configuration\Fixture\IntegerCastProcessor;
use KaririCode\Contract\Tests\Unit\Environment\Fixture\ArrayEnvironmentResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ValueProcessorTest extends TestCase
{
    #[Test]
    public function supportsOnlyTheValuesItHandles(): void
    {
        $cast = new IntegerCastProcessor();

        self::assertTrue($cast->supports('int:8080'));
        self::assertFalse($cast->supports('8080'));
        self::assertFalse($cast->supports(8080));
        self::assertFalse($cast->supports(null));
    }

    #[Test]
    public function processTransformsASupportedValue(): void
    {
        self::assertSame(8080, (new IntegerCastProcessor())->process('int:8080'));
    }

    #[Test]
    public function processRejectsAValueItCannotTransform(): void
    {
        $this->expectException(RuntimeException::class);

        (new IntegerCastProcessor())->process('int:eighty');
    }

    #[Test]
    public function returningTheInputUnchangedIsValid(): void
    {
        $passThrough = new class implements ValueProcessor {
            public function supports(mixed $value): bool
            {
                return true;
            }

            public function process(mixed $value): mixed
            {
                return $value;
            }
        };

        self::assertSame(['nested' => true], $passThrough->process(['nested' => true]));
    }

    #[Test]
    public function aChainAppliesEachSupportingProcessorInOrder(): void
    {
        $placeholders = new EnvironmentPlaceholderProcessor(new ArrayEnvironmentResolver(['PORT' => 'int:8080']));
        $cast = new IntegerCastProcessor();

        self::assertSame(8080, self::chain('${PORT}', $placeholders, $cast));
        self::assertSame('int:8080', self::chain('${PORT}', $cast, $placeholders));
        self::assertSame('plain', self::chain('plain', $placeholders, $cast));
    }

    private static function chain(mixed $value, ValueProcessor ...$processors): mixed
    {
        foreach ($processors as $processor) {
            if ($processor->supports($value)) {
                $value = $processor->process($value);
            }
        }

        return $value;
    }
}
