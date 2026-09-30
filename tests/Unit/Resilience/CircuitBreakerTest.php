<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience;

use KaririCode\Contract\Resilience\CallNotPermittedException;
use KaririCode\Contract\Resilience\CircuitBreaker;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\CountingCircuitBreaker;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CircuitBreakerTest extends TestCase
{
    #[Test]
    public function executeReturnsWhatTheOperationReturned(): void
    {
        $breaker = new CountingCircuitBreaker(failureThreshold: 3, retryAfterSeconds: 30);
        $receipt = new stdClass();

        self::assertSame($receipt, $breaker->execute(static fn(): stdClass => $receipt));
        self::assertSame(['total' => 10], $breaker->execute(static fn(): array => ['total' => 10]));
        self::assertNull($breaker->execute(static fn(): mixed => null));
    }

    #[Test]
    public function executeRethrowsTheOperationExceptionUnchanged(): void
    {
        $breaker = new CountingCircuitBreaker(failureThreshold: 3, retryAfterSeconds: 30);
        $failure = new LogicException('gateway down');

        try {
            $breaker->execute(static fn(): never => throw $failure);
            self::fail('The exception of the operation was swallowed.');
        } catch (LogicException $caught) {
            self::assertSame($failure, $caught);
        }
    }

    #[Test]
    public function anOpenCircuitRejectsWithoutRunningTheOperation(): void
    {
        $breaker = new CountingCircuitBreaker(failureThreshold: 1, retryAfterSeconds: 30);
        self::failOnce($breaker);
        $ran = false;

        try {
            $breaker->execute(static function () use (&$ran): void {
                $ran = true;
            });
            self::fail('An open circuit admitted the call.');
        } catch (CallNotPermittedException $rejected) {
            self::assertSame(30, $rejected->retryAfterSeconds);
        }

        self::assertFalse($ran);
    }

    #[Test]
    public function isOpenHasNoSideEffect(): void
    {
        $breaker = new CountingCircuitBreaker(failureThreshold: 2, retryAfterSeconds: 30);
        self::failOnce($breaker);

        for ($i = 0; $i < 10; ++$i) {
            self::assertFalse($breaker->isOpen());
        }

        self::failOnce($breaker);

        self::assertTrue($breaker->isOpen());
        self::assertTrue($breaker->isOpen());
    }

    private static function failOnce(CircuitBreaker $breaker): void
    {
        try {
            $breaker->execute(static fn(): never => throw new LogicException('down'));
        } catch (LogicException) {
            // The failure is what the test needs; the breaker has recorded it.
        }
    }
}
