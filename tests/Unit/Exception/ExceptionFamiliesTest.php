<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Exception;

use KaririCode\Contract\Exception\Configuration;
use KaririCode\Contract\Exception\ContainerFailure;
use KaririCode\Contract\Exception\ExceptionInterface;
use KaririCode\Contract\Exception\NotFound;
use KaririCode\Contract\Exception\Protocol;
use KaririCode\Contract\Exception\Runtime;
use KaririCode\Contract\Exception\Validation;
use KaririCode\Contract\Resilience\CallNotPermittedException;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\CircularDependency;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\InvalidEmail;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\InvalidQuota;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\MalformedFrame;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\StoreDown;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\TenantMissing;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\CallRejected;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

final class ExceptionFamiliesTest extends TestCase
{
    private const array FAMILIES = [Validation::class, NotFound::class, Configuration::class, Protocol::class, ContainerFailure::class, Runtime::class];

    /**
     * @return iterable<string, array{Throwable, int}>
     */
    public static function failures(): iterable
    {
        yield 'validation' => [new InvalidEmail('not an e-mail'), 422];
        yield 'not found' => [new TenantMissing('tenant 42'), 404];
        yield 'protocol' => [new MalformedFrame('bad frame'), 400];
        yield 'configuration' => [new InvalidQuota('limit 0'), 500];
        yield 'container' => [new CircularDependency('a -> b -> a'), 500];
        yield 'runtime' => [new StoreDown('redis down'), 500];
        yield 'open circuit' => [new CallRejected(30), 503];
        yield 'foreign' => [new \RuntimeException('not ours'), 500];
    }

    #[Test]
    #[DataProvider('failures')]
    public function anEdgeMapsEachFamilyWithoutKnowingTheComponent(Throwable $failure, int $status): void
    {
        self::assertSame($status, self::statusOf($failure));
    }

    #[Test]
    #[DataProvider('failures')]
    public function anEcosystemExceptionBelongsToExactlyOneFamily(Throwable $failure, int $status): void
    {
        $families = array_values(array_filter(self::FAMILIES, static fn(string $family): bool => $failure instanceof $family));

        self::assertCount($failure instanceof ExceptionInterface ? 1 : 0, $families);
    }

    #[Test]
    public function anOpenCircuitIsARuntimeFaultThatKeepsItsDelay(): void
    {
        $rejection = new CallRejected(30);

        self::assertInstanceOf(Runtime::class, $rejection);
        self::assertInstanceOf(ExceptionInterface::class, $rejection);
        self::assertSame(30, $rejection->retryAfterSeconds);
    }

    #[Test]
    public function theRootCatchesEveryFamily(): void
    {
        $caught = 0;

        foreach ([new InvalidEmail('x'), new TenantMissing('x'), new InvalidQuota('x'), new MalformedFrame('x'), new CircularDependency('x'), new StoreDown('x')] as $failure) {
            try {
                throw $failure;
            } catch (ExceptionInterface) {
                ++$caught;
            }
        }

        self::assertSame(6, $caught);
    }

    /** The mapping an HTTP edge applies (RFC 9110); the contract itself carries no transport data. */
    private static function statusOf(Throwable $failure): int
    {
        return match (true) {
            $failure instanceof CallNotPermittedException => 503,
            $failure instanceof Validation => 422,
            $failure instanceof NotFound => 404,
            $failure instanceof Protocol => 400,
            default => 500,
        };
    }
}
