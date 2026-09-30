<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience\Fixture;

use KaririCode\Contract\Resilience\CircuitBreaker;
use Override;
use Throwable;

/**
 * Minimal CircuitBreaker: opens after N consecutive failures and never
 * recovers. Enough to exercise the contract; kariricode/circuit-breaker
 * is the real implementation.
 *
 * Example: `$breaker = new CountingCircuitBreaker(failureThreshold: 3, retryAfterSeconds: 30);`
 */
final class CountingCircuitBreaker implements CircuitBreaker
{
    private int $consecutiveFailures = 0;

    public function __construct(
        private readonly int $failureThreshold,
        private readonly int $retryAfterSeconds,
    ) {}

    #[Override]
    public function execute(callable $operation): mixed
    {
        if ($this->isOpen()) {
            throw new CallRejected($this->retryAfterSeconds);
        }

        try {
            $result = $operation();
        } catch (Throwable $failure) {
            ++$this->consecutiveFailures;

            throw $failure;
        }

        $this->consecutiveFailures = 0;

        return $result;
    }

    #[Override]
    public function isOpen(): bool
    {
        return $this->consecutiveFailures >= $this->failureThreshold;
    }
}
