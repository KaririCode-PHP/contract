<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience\Fixture;

use InvalidArgumentException;
use KaririCode\Contract\Resilience\RateLimitDecision;
use KaririCode\Contract\Resilience\RateLimiter;
use Override;

/**
 * Minimal RateLimiter: $limit units per key that never refill until
 * reset(); a denial always says "one window from now". Process memory only.
 *
 * Example: `$limiter = new FixedQuotaRateLimiter(limit: 5, windowSeconds: 60);`
 */
final class FixedQuotaRateLimiter implements RateLimiter
{
    /** @var array<string, int> */
    private array $used = [];

    public function __construct(
        private readonly int $limit,
        private readonly int $windowSeconds,
    ) {}

    #[Override]
    public function attempt(string $key, int $cost = 1): RateLimitDecision
    {
        if ($cost < 1 || $cost > $this->limit) {
            throw new InvalidArgumentException(sprintf('Cost %d can never be admitted.', $cost));
        }

        $used = $this->used[$key] ?? 0;

        if ($used + $cost > $this->limit) {
            return $this->decision(allowed: false, used: $used);
        }

        $this->used[$key] = $used + $cost;

        return $this->decision(allowed: true, used: $used + $cost);
    }

    #[Override]
    public function peek(string $key): RateLimitDecision
    {
        $used = $this->used[$key] ?? 0;

        return $this->decision(allowed: $used + 1 <= $this->limit, used: $used);
    }

    #[Override]
    public function reset(string $key): void
    {
        unset($this->used[$key]);
    }

    private function decision(bool $allowed, int $used): Decision
    {
        return new Decision(
            allowed: $allowed,
            limit: $this->limit,
            remaining: $this->limit - $used,
            retryAfter: $allowed ? 0 : $this->windowSeconds,
            resetAfter: $used > 0 ? $this->windowSeconds : 0,
        );
    }
}
