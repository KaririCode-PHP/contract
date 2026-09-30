<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience\Fixture;

use KaririCode\Contract\Resilience\RateLimitDecision;

/**
 * Minimal decision: readonly promoted properties satisfy the interface's
 * get-only properties, with no hook (E-013).
 *
 * Example: `new Decision(allowed: false, limit: 5, remaining: 0, retryAfter: 60, resetAfter: 60);`
 */
final readonly class Decision implements RateLimitDecision
{
    public function __construct(
        public bool $allowed,
        public int $limit,
        public int $remaining,
        public int $retryAfter,
        public int $resetAfter,
    ) {}
}
