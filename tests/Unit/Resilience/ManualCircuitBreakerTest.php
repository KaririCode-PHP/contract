<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience;

use InvalidArgumentException;
use KaririCode\Contract\Resilience\CallNotPermittedException;
use KaririCode\Contract\Resilience\CircuitBreaker;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\CountingManualCircuitBreaker;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ManualCircuitBreakerTest extends TestCase
{
    #[Test]
    public function tryAcquirePermitReturnsNullInsteadOfThrowingWhenOpen(): void
    {
        $breaker = self::openBreaker();

        self::assertNull($breaker->tryAcquirePermit());
        self::assertTrue($breaker->isOpen());
    }

    #[Test]
    public function acquirePermitReportsTheRejectionWithItsDelay(): void
    {
        try {
            self::openBreaker()->acquirePermit();
            self::fail('An open circuit issued a permit.');
        } catch (CallNotPermittedException $rejected) {
            self::assertSame(30, $rejected->retryAfterSeconds);
        }
    }

    #[Test]
    public function theBreakerDecidesWhichFailuresCount(): void
    {
        $breaker = new CountingManualCircuitBreaker(failureThreshold: 1, retryAfterSeconds: 30);

        $breaker->recordFailure(self::permitOf($breaker), new InvalidArgumentException('bad input'));
        $breaker->recordFailure(self::permitOf($breaker), new InvalidArgumentException('bad input'));
        self::assertFalse($breaker->isOpen(), 'invalid input is not a dependency failure');

        $breaker->recordFailure(self::permitOf($breaker), new RuntimeException('dependency down'));
        self::assertTrue($breaker->isOpen());
    }

    #[Test]
    public function aReleasedPermitRecordsNothing(): void
    {
        $breaker = new CountingManualCircuitBreaker(failureThreshold: 1, retryAfterSeconds: 30);

        $breaker->releasePermit(self::permitOf($breaker));

        self::assertFalse($breaker->isOpen());
        self::assertNotNull($breaker->tryAcquirePermit());
    }

    #[Test]
    public function aPermitSettlesExactlyOnce(): void
    {
        $breaker = new CountingManualCircuitBreaker(failureThreshold: 3, retryAfterSeconds: 30);
        $permit = self::permitOf($breaker);
        $breaker->recordSuccess($permit);

        $this->expectException(LogicException::class);

        $breaker->recordFailure($permit, new RuntimeException('late outcome'));
    }

    #[Test]
    public function aPermitIsSettledOnlyByTheBreakerThatIssuedIt(): void
    {
        $issuer = new CountingManualCircuitBreaker(failureThreshold: 3, retryAfterSeconds: 30);
        $other = new CountingManualCircuitBreaker(failureThreshold: 3, retryAfterSeconds: 30);

        $this->expectException(LogicException::class);

        $other->recordSuccess(self::permitOf($issuer));
    }

    #[Test]
    public function executeIsTheSameProtocolInOneStep(): void
    {
        $breaker = new CountingManualCircuitBreaker(failureThreshold: 1, retryAfterSeconds: 30);

        self::assertInstanceOf(CircuitBreaker::class, $breaker);
        self::assertSame('ok', $breaker->execute(static fn(): string => 'ok'));

        try {
            $breaker->execute(static fn(): never => throw new RuntimeException('down'));
        } catch (RuntimeException) {
            // Counted by execute() through recordFailure().
        }

        self::assertTrue($breaker->isOpen());
    }

    private static function openBreaker(): CountingManualCircuitBreaker
    {
        $breaker = new CountingManualCircuitBreaker(failureThreshold: 1, retryAfterSeconds: 30);
        $breaker->recordFailure(self::permitOf($breaker), new RuntimeException('down'));

        return $breaker;
    }

    private static function permitOf(CountingManualCircuitBreaker $breaker): \KaririCode\Contract\Resilience\CallPermit
    {
        $permit = $breaker->tryAcquirePermit();
        self::assertNotNull($permit);

        return $permit;
    }
}
