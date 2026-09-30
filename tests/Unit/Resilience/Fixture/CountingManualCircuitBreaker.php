<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience\Fixture;

use InvalidArgumentException;
use KaririCode\Contract\Resilience\CallPermit;
use KaririCode\Contract\Resilience\ManualCircuitBreaker;
use LogicException;
use Override;
use Throwable;

/**
 * Minimal ManualCircuitBreaker: opens after N consecutive counted failures,
 * never recovers, and ignores InvalidArgumentException (bad input is not a
 * dependency failure). kariricode/circuit-breaker is the real one.
 *
 * Example: `$breaker = new CountingManualCircuitBreaker(failureThreshold: 3, retryAfterSeconds: 30);`
 */
final class CountingManualCircuitBreaker implements ManualCircuitBreaker
{
    private int $consecutiveFailures = 0;

    public function __construct(
        private readonly int $failureThreshold,
        private readonly int $retryAfterSeconds,
    ) {}

    #[Override]
    public function tryAcquirePermit(): ?CallPermit
    {
        return $this->isOpen() ? null : new PermitToken($this);
    }

    #[Override]
    public function acquirePermit(): CallPermit
    {
        return $this->tryAcquirePermit() ?? throw new CallRejected($this->retryAfterSeconds);
    }

    #[Override]
    public function recordSuccess(CallPermit $permit): void
    {
        $this->settle($permit);
        $this->consecutiveFailures = 0;
    }

    #[Override]
    public function recordFailure(CallPermit $permit, Throwable $error): void
    {
        $this->settle($permit);

        if (!$error instanceof InvalidArgumentException) {
            ++$this->consecutiveFailures;
        }
    }

    #[Override]
    public function releasePermit(CallPermit $permit): void
    {
        $this->settle($permit);
    }

    #[Override]
    public function execute(callable $operation): mixed
    {
        $permit = $this->acquirePermit();

        try {
            $result = $operation();
        } catch (Throwable $error) {
            $this->recordFailure($permit, $error);

            throw $error;
        }

        $this->recordSuccess($permit);

        return $result;
    }

    #[Override]
    public function isOpen(): bool
    {
        return $this->consecutiveFailures >= $this->failureThreshold;
    }

    private function settle(CallPermit $permit): void
    {
        if (!$permit instanceof PermitToken || $permit->issuer !== $this) {
            throw new LogicException('The permit was issued by another breaker.');
        }

        $permit->settle();
    }
}
