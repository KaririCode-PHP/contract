<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

/**
 * Admission control for a shared resource: decides, without blocking,
 * whether the work identified by a key may proceed now.
 *
 * A key partitions the quota (an account, a client address, a pipeline
 * stage); the quota itself (limit, period, burst, algorithm) belongs to
 * the limiter instance, not to the call. A denial is a normal outcome,
 * returned as a RateLimitDecision, never an exception.
 *
 * Preconditions: $cost is at least 1 and no larger than the limiter's
 * capacity; implementations reject any other cost with an exception,
 * because such a call could never be admitted. peek() is the read-only
 * question.
 *
 * Thread-safety: implementations keep state outside the PHP process
 * (Redis) and decide atomically, so every worker shares one quota. State
 * kept in process memory starts over on every PHP-FPM request.
 *
 * Example:
 *
 *     $decision = $limiter->attempt('stage:enrich');
 *     if (!$decision->allowed) {
 *         // nothing was consumed; the same call can succeed in $decision->retryAfter seconds
 *     }
 *
 * References: Resilience4j, "RateLimiter"; IETF draft-ietf-httpapi-ratelimit-headers.
 *
 * @see https://resilience4j.readme.io/docs/ratelimiter
 */
interface RateLimiter
{
    /** Consumes $cost units when allowed; consumes nothing when denied. */
    public function attempt(string $key, int $cost = 1): RateLimitDecision;

    /** Reports whether one unit would be admitted now, consuming nothing. */
    public function peek(string $key): RateLimitDecision;

    /**
     * Restores the full quota of $key.
     *
     * @throws \RuntimeException when the state store cannot be reached
     */
    public function reset(string $key): void;
}
