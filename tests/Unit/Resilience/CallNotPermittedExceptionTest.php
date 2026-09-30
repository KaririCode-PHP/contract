<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience;

use Error;
use KaririCode\Contract\Resilience\CallNotPermittedException;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\CallRejected;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

final class CallNotPermittedExceptionTest extends TestCase
{
    #[Test]
    public function aReadonlyPropertySatisfiesTheGetRequirementWithoutAHook(): void
    {
        $rejection = new CallRejected(12);
        $declared = new ReflectionProperty(CallNotPermittedException::class, 'retryAfterSeconds');
        $implemented = new ReflectionProperty(CallRejected::class, 'retryAfterSeconds');

        self::assertInstanceOf(CallNotPermittedException::class, $rejection);
        self::assertSame(12, $rejection->retryAfterSeconds);
        self::assertTrue($declared->isAbstract());
        self::assertSame(['get'], array_keys($declared->getHooks()));
        self::assertTrue($implemented->isReadOnly());
        self::assertFalse($implemented->hasHooks());
    }

    #[Test]
    public function theDelayIsNullWhenUnknown(): void
    {
        self::assertNull((new CallRejected(null))->retryAfterSeconds);
    }

    #[Test]
    public function theDelayCannotBeReassigned(): void
    {
        $rejection = new CallRejected(5);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('readonly');

        $rejection->retryAfterSeconds = 1;
    }

    #[Test]
    public function theContractAddsOnlyTheDelayToThrowable(): void
    {
        $contract = new ReflectionClass(CallNotPermittedException::class);
        $ownMethods = array_filter(
            $contract->getMethods(),
            static fn(ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $contract->getName(),
        );
        $properties = array_map(
            static fn(ReflectionProperty $property): string => $property->getName(),
            $contract->getProperties(),
        );

        self::assertTrue($contract->isSubclassOf(Throwable::class));
        self::assertSame([], $ownMethods);
        self::assertSame(['retryAfterSeconds'], $properties);
    }

    #[Test]
    public function hooksOnReadonlyPropertiesDoNotCompilePerErrataE013(): void
    {
        $readonlyWithHook = self::lint(
            'final readonly class Money { public function __construct(public int $cents) {} '
            . 'public string $label { get => (string) $this->cents; } }',
        );
        $mutableWithHook = self::lint(
            'final class Money { public function __construct(private int $cents) {} '
            . 'public string $label { get => (string) $this->cents; } }',
        );

        self::assertNotSame(0, $readonlyWithHook['exit']);
        self::assertStringContainsString('Hooked properties cannot be readonly', $readonlyWithHook['output']);
        self::assertSame(0, $mutableWithHook['exit'], $mutableWithHook['output']);
    }

    /**
     * @return array{exit: int, output: string}
     */
    private static function lint(string $declaration): array
    {
        $file = tempnam(sys_get_temp_dir(), 'e013');
        self::assertIsString($file);
        file_put_contents($file, "<?php\n" . $declaration . "\n");

        $output = [];
        $exit = 0;

        try {
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exit);
        } finally {
            unlink($file);
        }

        return ['exit' => $exit, 'output' => implode("\n", $output)];
    }
}
